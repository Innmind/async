<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

use Innmind\Async\{
    Scope,
    Suspension,
    Wait,
};
use Innmind\TimeContinuum\Clock;

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
    public function next(Clock $clock, Wait\IO $result): self|Resumable
    {
        $next = $this->suspension->next(
            $clock,
            $result,
        );

        if ($next instanceof Suspension) {
            return new self(
                $this->scope,
                $this->fiber,
                $next,
            );
        }

        return Resumable::of($this->scope, $this->fiber, $next);
    }

    public function suspension(): Suspension
    {
        return $this->suspension;
    }
}
