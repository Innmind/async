<?php
declare(strict_types = 1);

namespace Innmind\Async\Task;

use Innmind\Async\Suspension;

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

    public function next(): self|Resumable
    {
        // todo check from argument if suspension is fulfilled
        if (false) {
            return $this;
        }

        $result = null; // todo from suspension result

        return Resumable::of($this->fiber, $result);
    }
}
