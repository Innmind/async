<?php
declare(strict_types = 1);

namespace Innmind\Async\Scheduler;

use Innmind\Async\{
    Scope,
    Scope\Continuation,
    Wait,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

/**
 * @template C
 */
final class Sink
{
    /**
     * @param C $carry
     */
    private function __construct(
        private OperatingSystem $sync,
        private mixed $carry,
    ) {
    }

    /**
     * @template A
     *
     * @param A $carry
     *
     * @return self<A>
     */
    public static function of(
        OperatingSystem $sync,
        mixed $carry,
    ): self {
        return new self($sync, $carry);
    }

    /**
     * @param callable(C, OperatingSystem, Continuation<C>, Sequence<mixed>): Continuation<C> $scope
     *
     * @return C
     */
    public function with(callable $scope): mixed
    {
        $state = State::new(Scope::of(
            $scope,
            $this->carry,
        ));

        do {
            [$state, $terminated] = $state
                ->next($this->sync)
                ->wait(
                    $this->sync,
                    Wait::nothing(),
                );
        } while (\is_null($terminated));

        return $terminated->carry();
    }
}
