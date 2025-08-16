<?php
declare(strict_types = 1);

namespace Innmind\Async\Scheduler;

use Innmind\Async\{
    Scope,
    Task,
    Wait,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Sequence,
    Predicate\Instance,
};

final class State
{
    /**
     * @param Sequence<Task\Suspended|Task\Resumable|Task\Resumable> $tasks
     * @param Sequence<mixed> $results
     */
    private function __construct(
        private Scope\Uninitialized|Scope\Suspended|Scope\Resumable|Scope\Restartable|Scope\Wakeable|Scope\Terminated $scope,
        private Sequence $tasks,
        private Sequence $results,
    ) {
    }

    public static function new(
        Scope\Uninitialized|Scope\Suspended|Scope\Resumable|Scope\Restartable|Scope\Wakeable|Scope\Terminated $scope,
    ): self {
        return new self(
            $scope,
            Sequence::of(),
            Sequence::of(),
        );
    }

    public function next(OperatingSystem $sync): self
    {
        $scope = match (true) {
            $this->scope instanceof Scope\Uninitialized => $this->scope->next($this->async($sync)),
            $this->scope instanceof Scope\Suspended => $this->scope, // only the wait can advance
            $this->scope instanceof Scope\Resumable => $this->scope->next(),
            $this->scope instanceof Scope\Restartable => $this->scope->next(
                $this->async($sync),
                $this->results,
            ),
            !$this->results->empty() &&
            $this->scope instanceof Scope\Wakeable => $this->scope->next(
                $this->async($sync),
                $this->results,
            ),
            $this->scope instanceof Scope\Terminated => $this->scope->next(),
        };
        $results = match (true) {
            $this->scope instanceof Scope\Restartable => $this->results->clear(),
            !$this->results->empty() &&
            $this->scope instanceof Scope\Wakeable => $this->results->clear(),
            default => $this->results,
        };
        $tasks = match (true) {
            $scope instanceof Scope\Restartable => $scope->tasks(),
            $scope instanceof Scope\Wakeable => $scope->tasks(),
            $scope instanceof Scope\Terminated => $scope->tasks(),
            default => Sequence::of(),
        };

        // The new tasks are appended after in order to prioritize finishing
        // existing tasks. If exisiting ones can be finished in this iteration
        // then it allows to free memory early.
        // If the new tasks would be prioritized then it would need to allocate
        // new memory to start these tasks and eventually then free memory for
        // existing tasks about to finish.
        // The end result is the same but this way it should have the lowest
        // memory footprint.
        $tasks = $this
            ->tasks
            ->map(static fn($task) => match (true) {
                $task instanceof Task\Suspended => $task, // only the wait can advance
                $task instanceof Task\Resumable => $task->next(),
            })
            ->append(
                $tasks
                    ->map(Task\Uninitialized::of(...))
                    ->map(fn($task) => $task->next($this->async($sync))),
            );
        $results = $results->append(
            $tasks
                ->keep(Instance::of(Task\Terminated::class))
                ->map(static fn($task): mixed => $task->returned()),
        );
        $tasks = $tasks->keep(
            Instance::of(Task\Suspended::class)->or(
                Instance::of(Task\Resumable::class),
            ),
        );

        return new self(
            $scope,
            $tasks,
            $results,
        );
    }

    /**
     * @return array{self, ?Scope\Terminated}
     */
    public function wait(Wait $wait): array
    {
        if (
            $this->scope instanceof Scope\Terminated &&
            $this->tasks->empty()
        ) {
            return [$this, $this->scope];
        }

        if (
            $this->scope instanceof Scope\Wakeable &&
            $this->tasks->empty() &&
            $this->results->empty()
        ) {
            return [$this, $this->scope->terminate()];
        }

        // todo wait

        return [$this, null];
    }

    private function async(OperatingSystem $sync): OperatingSystem
    {
        // todo
        return $sync;
    }
}
