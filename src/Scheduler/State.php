<?php
declare(strict_types = 1);

namespace Innmind\Async\Scheduler;

use Innmind\Async\{
    Scope,
    Task,
    Wait,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\HttpTransport\Curl;
use Innmind\TimeWarp\Halt;
use Innmind\TimeContinuum\Period;
use Innmind\IO\IO;
use Innmind\Immutable\{
    Sequence,
    Predicate\Instance,
};

/**
 * @template C
 */
final class State
{
    /**
     * @param Scope\Uninitialized<C>|Scope\Suspended<C>|Scope\Resumable<C>|Scope\Restartable<C>|Scope\Wakeable<C>|Scope\Terminated<C> $scope
     * @param Sequence<Task\Suspended|Task\Resumable> $tasks
     * @param Sequence<mixed> $results
     */
    private function __construct(
        private Scope\Uninitialized|Scope\Suspended|Scope\Resumable|Scope\Restartable|Scope\Wakeable|Scope\Terminated $scope,
        private Sequence $tasks,
        private Sequence $results,
    ) {
    }

    /**
     * @template A
     *
     * @param Scope\Uninitialized<A>|Scope\Suspended<A>|Scope\Resumable<A>|Scope\Restartable<A>|Scope\Wakeable<A>|Scope\Terminated<A> $scope
     *
     * @return self<A>
     */
    public static function new(
        Scope\Uninitialized|Scope\Suspended|Scope\Resumable|Scope\Restartable|Scope\Wakeable|Scope\Terminated $scope,
    ): self {
        return new self(
            $scope,
            Sequence::of(),
            Sequence::of(),
        );
    }

    /**
     * @return self<C>
     */
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
            $this->scope instanceof Scope\Wakeable => match ($this->results->empty()) {
                true => $this->scope,
                false => $this->scope->next(
                    $this->async($sync),
                    $this->results,
                ),
            },
            $this->scope instanceof Scope\Terminated => $this->scope->next(),
        };
        $results = match (true) {
            $this->scope instanceof Scope\Restartable => $this->results->clear(),
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

        // At this point the scope may be restartable with tasks being suspended
        // meaning the scope will have to wait the amount of time specified by
        // the tasks' suspension before being restarted. This means that the
        // whole thing could be slower than imagined.
        // However we do not restart the scope, by recursively calling
        // `$this->next()`, as it may result in an accumulation of tasks that
        // are suspended and never advanced (never resumed after a halt or
        // streams never read). This case could happen if the user defined scope
        // is designed as an infinite loop that always schedule new tasks.

        return new self(
            $scope,
            $tasks,
            $results,
        );
    }

    /**
     * @return array{self<C>, ?Scope\Terminated<C>}
     */
    public function wait(
        OperatingSystem $sync,
        Wait $wait,
    ): array {
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

        if ($this->scope instanceof Scope\Suspended) {
            $wait = $wait->with($this->scope->suspension());
        }

        $suspended = $this->tasks->keep(Instance::of(Task\Suspended::class));
        $wait = $suspended->reduce(
            $wait,
            static fn(Wait $wait, $task) => $wait->with($task->suspension()),
        );

        $result = $wait($sync);

        if (\is_null($result)) {
            return [$this, null];
        }

        $scope = $this->scope;
        $resumable = $this->tasks->keep(Instance::of(Task\Resumable::class));

        if ($scope instanceof Scope\Suspended) {
            $scope = $scope->next($sync->clock(), $result);
        }

        return [
            new self(
                $scope,
                $suspended
                    ->map(static fn($task) => $task->next(
                        $sync->clock(),
                        $result,
                    ))
                    ->prepend($resumable),
                $this->results,
            ),
            null,
        ];
    }

    private function async(OperatingSystem $sync): OperatingSystem
    {
        $halt = Halt\Async::of($sync->clock());
        $io = IO::async($sync->clock());
        // todo handle max concurrency + ssl configuration
        // todo build a native client based on innmind/io to better integrate in
        // this system.
        $http = Curl::of(
            $sync->clock(),
            $io,
        )
            ->heartbeat(
                Period::millisecond(10), // this is blocking the active task so it needs to be low
                static fn() => $halt(Period::millisecond(1))->unwrap(), // this allows to jump between tasks
            );
        // todo handle process signals

        return $sync->map(
            static fn($config) => $config
                ->haltProcessVia($halt)
                ->useHttpTransport($http)
                ->withIO($io),
        );
    }
}
