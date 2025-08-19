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
 *
 * @internal
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
    #[\NoDiscard]
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
    #[\NoDiscard]
    public function next(
        Clock $clock,
        Wait\IO|Wait\Time $result,
    ): self|Resumable {
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

    /**
     * @psalm-mutation-free
     */
    #[\NoDiscard]
    public function suspension(): Suspension
    {
        return $this->suspension;
    }
}
