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
     * @param Sequence<Task\Suspended|Task\Resumable> $running
     */
    private function __construct(
        private Config $config,
        private Sequence $running,
        // todo split between suspended and resumable
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
        );
    }

    public function empty(): bool
    {
        return $this->running->empty();
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
        $tasks = $this
            ->running
            ->map(static fn($task) => match (true) {
                $task instanceof Task\Suspended => $task,  // only the wait can advance
                $task instanceof Task\Resumable => $task->next(),
            })
            ->append(
                $new
                    ->map(Task\Uninitialized::of(...))
                    ->map(fn($task) => $task->next($sync->map($this->config))),
            );
        $results = $tasks
            ->keep(Instance::of(Task\Terminated::class))
            ->map(static fn($task): mixed => $task->returned())
            ->exclude(static fn($value) => $value === Task\Discard::result);
        $tasks = $tasks->keep(
            Instance::of(Task\Suspended::class)->or(
                Instance::of(Task\Resumable::class),
            ),
        );

        return [
            new self(
                $this->config,
                $tasks,
            ),
            $results,
        ];
    }

    /**
     * @return Sequence<Suspension>
     */
    public function suspensions(): Sequence
    {
        return $this
            ->running
            ->keep(Instance::of(Task\Suspended::class))
            ->map(static fn($task) => $task->suspension());
    }

    public function awaited(
        OperatingSystem $sync,
        Wait\IO|Wait\Time $result,
    ): self {
        $suspended = $this->running->keep(Instance::of(Task\Suspended::class));

        return new self(
            $this->config,
            $this
                ->running
                ->keep(Instance::of(Task\Suspended::class))
                ->map(static fn($task) => $task->next(
                    $sync->clock(),
                    $result,
                ))
                ->prepend(
                    $this
                        ->running
                        ->keep(Instance::of(Task\Resumable::class)),
                ),
        );
    }
}
