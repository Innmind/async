<?php
declare(strict_types = 1);

namespace Innmind\Async\Task;

use Innmind\Async\Suspension;

/**
 * IO is ready or halt is finished
 */
final class Resumable
{
    private function __construct(
        private \Fiber $fiber,
        private mixed $result, // todo type
    ) {
    }

    public static function of(\Fiber $fiber, mixed $result): self
    {
        return new self($fiber, $result);
    }

    public function next(): Suspended|Terminated
    {
        /** @var ?Suspension */
        $return = $this->fiber->resume($this->result);

        if ($return instanceof Suspension) {
            return Suspended::of(
                $this->fiber,
                $return,
            );
        }

        return Terminated::of($this->fiber->getReturn());
    }
}
