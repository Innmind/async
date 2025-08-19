<?php
declare(strict_types = 1);

namespace Innmind\Async;

use Innmind\Async\Scope\Continuation;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

/**
 * @internal
 */
final class Scope
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private \Closure $scope,
    ) {
    }

    /**
     * @psalm-pure
     * @template C
     *
     * @param callable(C, OperatingSystem, Continuation<C>, Sequence<mixed>): Continuation<C> $scope
     *
     * @return Scope\Uninitialized<C>
     */
    #[\NoDiscard]
    public static function of(
        callable $scope,
        mixed $carry,
    ): Scope\Uninitialized {
        return Scope\Uninitialized::of(
            new self(\Closure::fromCallable($scope)),
            $carry,
        );
    }

    #[\NoDiscard]
    public function new(): \Fiber
    {
        return new \Fiber($this->scope);
    }
}
