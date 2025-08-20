<?php
declare(strict_types = 1);

namespace Innmind\Async\Task;

use Innmind\Async\{
    Suspension,
};
use Innmind\OperatingSystem\OperatingSystem;

/**
 * @internal
 */
final class Uninitialized
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private \Closure $task,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param callable(OperatingSystem) $task
     */
    #[\NoDiscard]
    public static function of(callable $task): self
    {
        return new self(
            \Closure::fromCallable($task),
        );
    }

    #[\NoDiscard]
    public function next(OperatingSystem $async): Suspended|Terminated
    {
        $fiber = new \Fiber($this->task);
        $return = Suspension::of($fiber->start($async));

        if ($return instanceof Suspension) {
            return Suspended::of(
                $fiber,
                $return,
            );
        }

        return Terminated::of($fiber->getReturn());
    }
}
