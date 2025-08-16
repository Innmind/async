<?php
declare(strict_types = 1);

namespace Innmind\Async\Scheduler;

use Innmind\Async\{
    Scope,
    Task,
    Wait,
};
use Innmind\Immutable\Sequence;

final class State
{
    private function __construct(
    ) {
    }

    /**
     * @param ?Sequence<Task\Uninitialized|Task\Suspended|Task\Resumable> $tasks
     * @param ?Sequence<mixed> $results
     */
    public static function new(
        Scope\Uninitialized|Scope\Suspended|Scope\Resumable|Scope\Restartable|Scope\Wakeable|Scope\Terminated $scope,
        ?Sequence $tasks = null,
        ?Sequence $results = null,
    ): self {
        return new self;
    }

    public function next(): self
    {
        // todo call scope|tasks that can do stuff
        return new self;
    }

    public function wait(Wait $wait): self
    {
        return new self;
    }
}
