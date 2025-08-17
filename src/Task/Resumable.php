<?php
declare(strict_types = 1);

namespace Innmind\Async\Task;

use Innmind\Async\{
    Suspension,
    Resumption,
};

/**
 * IO is ready or halt is finished
 */
final class Resumable
{
    private function __construct(
        private \Fiber $fiber,
        private Resumption $resumption,
    ) {
    }

    public static function of(\Fiber $fiber, Resumption $resumption): self
    {
        return new self($fiber, $resumption);
    }

    public function next(): Suspended|Terminated
    {
        $return = Suspension::of($this->fiber->resume(
            $this->resumption->unwrap(),
        ));

        if ($return instanceof Suspension) {
            return Suspended::of(
                $this->fiber,
                $return,
            );
        }

        return Terminated::of($this->fiber->getReturn());
    }
}
