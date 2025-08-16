<?php
declare(strict_types = 1);

namespace Innmind\Async\Task;

use Innmind\Async\{
    Suspension,
};
use Innmind\OperatingSystem\OperatingSystem;

final class Uninitialized
{
    private function __construct(
        private \Closure $task,
    ) {
    }

    public static function of(callable $task): self
    {
        return new self(
            \Closure::fromCallable($task),
        );
    }

    public function next(OperatingSystem $async): Suspended|Terminated
    {
        $fiber = new \Fiber($this->task);
        /** @var ?Suspension */
        $return = $fiber->start($async);

        if ($return instanceof Suspension) {
            return Suspended::of(
                $fiber,
                $return,
            );
        }

        return Terminated::of($fiber->getReturn());
    }
}
