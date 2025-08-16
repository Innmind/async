<?php
declare(strict_types = 1);

namespace Innmind\Async;

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
     */
    public static function of(
        callable $scope,
        mixed $carry,
    ): Scope\Uninitialized {
        return Scope\Uninitialized::of(
            new self(\Closure::fromCallable($scope)),
            $carry,
        );
    }

    public function new(): \Fiber
    {
        return new \Fiber($this->scope);
    }
}
