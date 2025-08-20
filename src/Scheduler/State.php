<?php
declare(strict_types = 1);

namespace Innmind\Async\Scheduler;

use Innmind\Async\{
    Scope\Uninitialized,
    Scope\Suspended,
    Scope\Resumable,
    Scope\Restartable,
    Scope\Wakeable,
    Scope\Terminated,
    Scope\Finished,
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
     * @param Uninitialized<C>|Suspended<C>|Resumable<C>|Restartable<C>|Wakeable<C>|Terminated<C>|Finished<C> $scope
     * @param Sequence<mixed> $results
     */
    private function __construct(
        private Uninitialized|Suspended|Resumable|Restartable|Wakeable|Terminated|Finished $scope,
        private Tasks $tasks,
        private Sequence $results,
        private Config\Provider $config,
    ) {
    }

    /**
     * @template A
     *
     * @param Uninitialized<A> $scope
     * @param ?int<2, max> $concurrencyLimit
     *
     * @return self<A>
     */
    #[\NoDiscard]
    public static function new(
        Uninitialized $scope,
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
        } while ($self->scope instanceof Restartable);

        return $self;
    }

    /**
     * @return array{self<C>, Terminated<C>|Finished<C>|null}
     */
    #[\NoDiscard]
    public function wait(
        OperatingSystem $sync,
        Wait $wait,
    ): array {
        if (
            $this->scope instanceof Finished &&
            $this->tasks->empty()
        ) {
            return [$this, $this->scope];
        }

        if (
            $this->scope instanceof Terminated &&
            $this->tasks->empty()
        ) {
            return [$this, $this->scope];
        }

        if (
            $this->scope instanceof Wakeable &&
            $this->tasks->empty() &&
            $this->results->empty()
        ) {
            return [$this, $this->scope->finish()];
        }

        if ($this->scope instanceof Suspended) {
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

        if ($scope instanceof Suspended) {
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
            $this->scope instanceof Uninitialized => $this->scope->next(
                $sync->map(($this->config)()),
            ),
            $this->scope instanceof Suspended => $this->scope, // only the wait can advance
            $this->scope instanceof Resumable => $this->scope->next(),
            $this->scope instanceof Restartable => $this->scope->next(
                $sync->map(($this->config)()),
                $this->results,
            ),
            $this->scope instanceof Wakeable => match ($this->results->empty()) {
                true => $this->scope->clear(), // clear tasks otherwise they're infinitely restarted
                false => $this->scope->next(
                    $sync->map(($this->config)()),
                    $this->results,
                ),
            },
            $this->scope instanceof Terminated => $this->scope,
            $this->scope instanceof Finished => $this->scope->next(),
        };
        $results = match (true) {
            $this->scope instanceof Restartable => $this->results->clear(),
            $this->scope instanceof Wakeable => $this->results->clear(),
            $this->scope instanceof Terminated => $this->results->clear(),
            $this->scope instanceof Finished => $this->results->clear(),
            default => $this->results,
        };
        $newTasks = match (true) {
            $scope instanceof Restartable => $scope->tasks(),
            $scope instanceof Wakeable => $scope->tasks(),
            $scope instanceof Finished => $scope->tasks(),
            default => Sequence::of(),
        };

        $tasks = $this->tasks;

        // We try to abort before advancing tasks as it may start new
        // unscheduled tasks. This way we prevent starting them and aborting
        // them right after.
        if ($scope instanceof Terminated) {
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
