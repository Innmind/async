<?php
declare(strict_types = 1);

namespace Innmind\Async\Task;

use Innmind\Async\{
    Suspension,
    Resumption,
};
use Innmind\Signals\{
    Async\Interceptor,
    Signal,
};

/**
 * IO is ready or halt is finished
 *
 * @internal
 */
final class Resumable
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private \Fiber $fiber,
        private Interceptor $interceptor,
        private Resumption $resumption,
    ) {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(
        \Fiber $fiber,
        Interceptor $interceptor,
        Resumption $resumption,
    ): self {
        return new self(
            $fiber,
            $interceptor,
            $resumption,
        );
    }

    #[\NoDiscard]
    public function next(): Suspended|Terminated
    {
        $return = Suspension::of($this->fiber->resume(
            $this->resumption->unwrap(),
        ));

        if ($return instanceof Suspension) {
            return Suspended::of(
                $this->fiber,
                $this->interceptor,
                $return,
            );
        }

        return Terminated::of($this->fiber->getReturn());
    }

    public function signal(Signal $signal): void
    {
        $this->interceptor->dispatch($signal);
    }
}
