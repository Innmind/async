<?php
declare(strict_types = 1);

namespace Innmind\Async\Scheduler;

use Innmind\Async\{
    Task,
    Wait,
    Suspension,
    Config,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Signals\Signal;
use Innmind\Immutable\{
    Sequence,
    Predicate\Instance,
};

/**
 * @internal
 * @psalm-immutable
 */
final class Tasks
{
    /**
     * @param ?int<2, max> $concurrencyLimit
     * @param Sequence<Task\Suspended> $suspended
     * @param Sequence<Task\Resumable> $resumable
     * @param Sequence<callable(OperatingSystem)> $unscheduled
     */
    private function __construct(
        private Config\Provider $config,
        private ?int $concurrencyLimit,
        private Sequence $suspended,
        private Sequence $resumable,
        private Sequence $unscheduled,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param ?int<2, max> $concurrencyLimit
     */
    #[\NoDiscard]
    public static function none(Config\Provider $config, ?int $concurrencyLimit): self
    {
        return new self(
            $config,
            $concurrencyLimit,
            Sequence::of(),
            Sequence::of(),
            Sequence::of(),
        );
    }

    #[\NoDiscard]
    public function empty(): bool
    {
        return $this->suspended->empty() &&
            $this->resumable->empty() &&
            $this->unscheduled->empty();
    }

    /**
     * @param Sequence<callable(OperatingSystem)> $new
     *
     * @return array{self, Sequence<mixed>}
     */
    #[\NoDiscard]
    public function next(
        OperatingSystem $sync,
        Sequence $new,
    ): array {
        // The new tasks are appended after in order to prioritize finishing
        // existing tasks. If exisiting ones can be finished in this iteration
        // then it allows to free memory early.
        // If the new tasks would be prioritized then it would need to allocate
        // new memory to start these tasks and eventually then free memory for
        // existing tasks about to finish.
        // The end result is the same but this way it should have the lowest
        // memory footprint.
        // Suspended tasks can only be advanced in the wait
        [$new, $unscheduled] = $this->throttle($new);
        $tasks = $this
            ->resumable
            ->map(static fn($task) => $task->next())
            ->append(
                $new
                    ->map(Task\Uninitialized::of(...))
                    ->map(fn($task) => $task->next(
                        $sync,
                        $this->config,
                    )),
            );
        $results = $tasks
            ->keep(Instance::of(Task\Terminated::class))
            ->map(static fn($task): mixed => $task->returned())
            ->exclude(static fn($value) => $value === Task\Discard::result);

        return [
            new self(
                $this->config,
                $this->concurrencyLimit,
                $this->suspended->append(
                    $tasks->keep(Instance::of(Task\Suspended::class)),
                ),
                $tasks->keep(Instance::of(Task\Resumable::class)),
                $unscheduled,
            ),
            $results,
        ];
    }

    /**
     * @return Sequence<Suspension>
     */
    #[\NoDiscard]
    public function suspensions(): Sequence
    {
        return $this->suspended->map(
            static fn($task) => $task->suspension(),
        );
    }

    #[\NoDiscard]
    public function awaited(
        OperatingSystem $sync,
        Wait\IO|Wait\Time $result,
    ): self {
        $tasks = $this->suspended->map(
            static fn($task) => $task->next(
                $sync->clock(),
                $result,
            ),
        );

        return new self(
            $this->config,
            $this->concurrencyLimit,
            $tasks->keep(Instance::of(Task\Suspended::class)),
            $this->resumable->append(
                $tasks->keep(Instance::of(Task\Resumable::class)),
            ),
            $this->unscheduled,
        );
    }

    #[\NoDiscard]
    public function abort(): self
    {
        // This is not ideal to not return a new version of tasks but it is a
        // mutating call after all 🤷‍♂️
        $_ = $this->suspended->foreach(
            static fn($task) => $task->signal(Signal::terminate),
        );
        $_ = $this->resumable->foreach(
            static fn($task) => $task->signal(Signal::terminate),
        );

        return new self(
            $this->config,
            $this->concurrencyLimit,
            $this->suspended,
            $this->resumable,
            $this->unscheduled->clear(),
        );
    }

    /**
     * @param Sequence<callable(OperatingSystem)> $new
     * @return array{
     *     Sequence<callable(OperatingSystem)>,
     *     Sequence<callable(OperatingSystem)>,
     * }
     */
    private function throttle(Sequence $new): array
    {
        if (\is_null($this->concurrencyLimit)) {
            return [$new, Sequence::of()];
        }

        $active = $this->suspended->size() + $this->resumable->size();
        $allowed = $this->concurrencyLimit - $active;

        if ($allowed < 1) {
            return [
                Sequence::of(),
                $this->unscheduled->append($new),
            ];
        }

        $tasks = $this->unscheduled->append($new);

        return [
            $tasks->take($allowed),
            $tasks->drop($allowed),
        ];
    }
}
