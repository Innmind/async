<?php
declare(strict_types = 1);

namespace Innmind\Async\Scheduler;

use Innmind\Async\{
    Scope,
    Wait,
    Config,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

/**
 * @internal
 * @template C
 */
final class State
{
    /**
     * @param Scope\Uninitialized<C>|Scope\Suspended<C>|Scope\Resumable<C>|Scope\Restartable<C>|Scope\Wakeable<C>|Scope\Aborted<C>|Scope\Terminated<C> $scope
     * @param Sequence<mixed> $results
     */
    private function __construct(
        private Scope\Uninitialized|Scope\Suspended|Scope\Resumable|Scope\Restartable|Scope\Wakeable|Scope\Aborted|Scope\Terminated $scope,
        private Tasks $tasks,
        private Sequence $results,
        private Config\Provider $config,
    ) {
    }

    /**
     * @template A
     *
     * @param Scope\Uninitialized<A> $scope
     * @param ?int<2, max> $concurrencyLimit
     *
     * @return self<A>
     */
    #[\NoDiscard]
    public static function new(
        Scope\Uninitialized $scope,
        Config\Provider $config,
        ?int $concurrencyLimit,
    ): self {
        return new self(
            $scope,
            Tasks::none($config, $concurrencyLimit),
            Sequence::of(),
            $config,
        );
    }

    /**
     * @return self<C>
     */
    #[\NoDiscard]
    public function next(OperatingSystem $sync): self
    {
        $self = $this;

        do {
            $self = $self->doNext($sync);
            // At this point the scope may be restartable with tasks being
            // suspended meaning the scope will have to wait the amount of time
            // specified by the tasks' suspension before being restarted. This
            // means that the whole thing could be slower than imagined.
            // We restart the scope to try to reach a point where it's suspended
            // hopefully to wait before scheduling new tasks. This way the scope
            // waits less time before scheduling new ones and prevents not
            // watching eventual streams or halting the _process_.
            // However if the user defined scope is designed as an infinite loop
            // scheduling new tasks each time that are suspended upon
            // initialization then it will result in an accumulation of
            // suspended tasks that will fill the process memory.
        } while ($self->scope instanceof Scope\Restartable);

        return $self;
    }

    /**
     * @return array{self<C>, Scope\Aborted<C>|Scope\Terminated<C>|null}
     */
    #[\NoDiscard]
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
            $this->scope instanceof Scope\Aborted &&
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

        $wait = $this
            ->tasks
            ->suspensions()
            ->reduce(
                $wait,
                static fn(Wait $wait, $suspension) => $wait->with($suspension),
            );

        $result = $wait($sync);

        if (\is_null($result)) {
            return [$this, null];
        }

        $scope = $this->scope;

        if ($scope instanceof Scope\Suspended) {
            $scope = $scope->next($sync->clock(), $result);
        }

        return [
            new self(
                $scope,
                $this->tasks->awaited($sync, $result),
                $this->results,
                $this->config,
            ),
            null,
        ];
    }

    /**
     * @return self<C>
     */
    private function doNext(OperatingSystem $sync): self
    {
        $scope = match (true) {
            $this->scope instanceof Scope\Uninitialized => $this->scope->next(
                $sync->map(($this->config)()),
            ),
            $this->scope instanceof Scope\Suspended => $this->scope, // only the wait can advance
            $this->scope instanceof Scope\Resumable => $this->scope->next(),
            $this->scope instanceof Scope\Restartable => $this->scope->next(
                $sync->map(($this->config)()),
                $this->results,
            ),
            $this->scope instanceof Scope\Wakeable => match ($this->results->empty()) {
                true => $this->scope->clear(), // clear tasks otherwise they're infinitely restarted
                false => $this->scope->next(
                    $sync->map(($this->config)()),
                    $this->results,
                ),
            },
            $this->scope instanceof Scope\Aborted => $this->scope,
            $this->scope instanceof Scope\Terminated => $this->scope->next(),
        };
        $results = match (true) {
            $this->scope instanceof Scope\Restartable => $this->results->clear(),
            $this->scope instanceof Scope\Wakeable => $this->results->clear(),
            $this->scope instanceof Scope\Aborted => $this->results->clear(),
            $this->scope instanceof Scope\Terminated => $this->results->clear(),
            default => $this->results,
        };
        $newTasks = match (true) {
            $scope instanceof Scope\Restartable => $scope->tasks(),
            $scope instanceof Scope\Wakeable => $scope->tasks(),
            $scope instanceof Scope\Terminated => $scope->tasks(),
            default => Sequence::of(),
        };

        $tasks = $this->tasks;

        // We try to abort before advancing tasks as it may start new
        // unscheduled tasks. This way we prevent starting them and aborting
        // them right after.
        if ($scope instanceof Scope\Aborted) {
            $tasks = $tasks->abort();
        }

        [$tasks, $newResults] = $tasks->next(
            $sync,
            $newTasks,
        );

        return new self(
            $scope,
            $tasks,
            $results->append($newResults),
            $this->config,
        );
    }
}
