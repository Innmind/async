<?php
declare(strict_types = 1);

namespace Innmind\Async\Scheduler;

use Innmind\Async\{
    Scope,
    Task,
    Wait,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

final class State
{
    /**
     * @param Sequence<Task\Uninitialized|Task\Suspended|Task\Resumable|Task\Resumable> $tasks
     * @param Sequence<mixed> $results
     */
    private function __construct(
        private Scope\Uninitialized|Scope\Suspended|Scope\Resumable|Scope\Restartable|Scope\Wakeable|Scope\Terminated $scope,
        private Sequence $tasks,
        private Sequence $results,
    ) {
    }

    /**
     * @param ?Sequence<Task\Uninitialized|Task\Suspended|Task\Resumable|Task\Resumable> $tasks
     * @param ?Sequence<mixed> $results
     */
    public static function new(
        Scope\Uninitialized|Scope\Suspended|Scope\Resumable|Scope\Restartable|Scope\Wakeable|Scope\Terminated $scope,
        ?Sequence $tasks = null,
        ?Sequence $results = null,
    ): self {
        return new self(
            $scope,
            $tasks ?? Sequence::of(),
            $results ?? Sequence::of(),
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
        };
        $tasks = $this->tasks->append(
            $tasks->map(Task\Uninitialized::of(...)),
        );

        // todo advance tasks

        return new self(
            $scope,
            $tasks,
            $results,
        );
    }

    public function wait(Wait $wait): self
    {
        return $this;
    }

    private function async(OperatingSystem $sync): OperatingSystem
    {
        // todo
        return $sync;
    }
}
