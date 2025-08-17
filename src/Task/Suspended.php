<?php
declare(strict_types = 1);

namespace Innmind\Async\Task;

use Innmind\Async\{
    Suspension,
    Wait,
};
use Innmind\TimeContinuum\Clock;

/**
 * Waiting for IO to be ready or halt to be finished
 */
final class Suspended
{
    private function __construct(
        private \Fiber $fiber,
        private Suspension $suspension,
    ) {
    }

    public static function of(\Fiber $fiber, Suspension $suspension): self
    {
        return new self($fiber, $suspension);
    }

    public function next(
        Clock $clock,
        Wait\IO $result,
    ): self|Resumable {
        $next = $this->suspension->next(
            $clock,
            $result,
        );

        if ($next instanceof Suspension) {
            return new self(
                $this->fiber,
                $next,
            );
        }

        return Resumable::of($this->fiber, $next);
    }

    public function suspension(): Suspension
    {
        return $this->suspension;
    }
}
