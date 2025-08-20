<?php
declare(strict_types = 1);

namespace Innmind\Async\Scheduler;

use Innmind\Async\{
    Scope,
    Scope\Continuation,
    Wait,
    Config,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

/**
 * @template C
 */
final class Sink
{
    /**
     * @psalm-mutation-free
     *
     * @param ?int<2, max> $concurrencyLimit
     * @param C $carry
     */
    private function __construct(
        private OperatingSystem $sync,
        private ?int $concurrencyLimit,
        private mixed $carry,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     * @template A
     *
     * @param ?int<2, max> $concurrencyLimit
     * @param A $carry
     *
     * @return self<A>
     */
    #[\NoDiscard]
    public static function of(
        OperatingSystem $sync,
        ?int $concurrencyLimit,
        mixed $carry,
    ): self {
        return new self($sync, $concurrencyLimit, $carry);
    }

    /**
     * @param callable(C, OperatingSystem, Continuation<C>, Sequence<mixed>): Continuation<C> $scope
     *
     * @return C
     */
    public function with(callable $scope): mixed
    {
        $state = State::new(
            Scope::of(
                $scope,
                $this->carry,
            ),
            Config\Provider::of($this->sync->clock()),
            $this->concurrencyLimit,
        );

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
