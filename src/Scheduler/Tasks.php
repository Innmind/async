<?php
declare(strict_types = 1);

namespace Innmind\Async\Scheduler;

use Innmind\Async\{
    Task,
    Wait,
    Suspension,
    Config\Async as Config,
};
use Innmind\OperatingSystem\OperatingSystem;
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
     * @param Sequence<Task\Suspended> $suspended
     * @param Sequence<Task\Resumable> $resumable
     */
    private function __construct(
        private Config $config,
        private Sequence $suspended,
        private Sequence $resumable,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function none(Config $config): self
    {
        return new self(
            $config,
            Sequence::of(),
            Sequence::of(),
        );
    }

    public function empty(): bool
    {
        return $this->suspended->empty() && $this->resumable->empty();
    }

    /**
     * @param Sequence<callable(OperatingSystem)> $new
     *
     * @return array{self, Sequence<mixed>}
     */
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
        $tasks = $this
            ->resumable
            ->map(static fn($task) => $task->next())
            ->append(
                $new
                    ->map(Task\Uninitialized::of(...))
                    ->map(fn($task) => $task->next($sync->map($this->config))),
            );
        $results = $tasks
            ->keep(Instance::of(Task\Terminated::class))
            ->map(static fn($task): mixed => $task->returned())
            ->exclude(static fn($value) => $value === Task\Discard::result);

        return [
            new self(
                $this->config,
                $this->suspended->append(
                    $tasks->keep(Instance::of(Task\Suspended::class)),
                ),
                $tasks->keep(Instance::of(Task\Resumable::class)),
            ),
            $results,
        ];
    }

    /**
     * @return Sequence<Suspension>
     */
    public function suspensions(): Sequence
    {
        return $this->suspended->map(
            static fn($task) => $task->suspension(),
        );
    }

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
            $tasks->keep(Instance::of(Task\Suspended::class)),
            $this->resumable->append(
                $tasks->keep(Instance::of(Task\Resumable::class)),
            ),
        );
    }
}
