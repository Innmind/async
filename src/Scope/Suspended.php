<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

use Innmind\Async\{
    Scope,
    Suspension,
};

/**
 * Waiting for IO to be ready or halt to be finished
 * @template C
 */
final class Suspended
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private Scope $scope,
        private \Fiber $fiber,
        private Suspension $suspension,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(
        Scope $scope,
        \Fiber $fiber,
        Suspension $suspension,
    ): self {
        return new self($scope, $fiber, $suspension);
    }

    /**
     * @return self<C>|Resumable<C>
     */
    public function next(): self|Resumable
    {
        // todo check from argument if suspension is fulfilled
        if (false) {
            return $this;
        }

        $result = null; // todo from suspension result

        return Resumable::of($this->scope, $this->fiber, $result);
    }
}
