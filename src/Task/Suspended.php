<?php
declare(strict_types = 1);

namespace Innmind\Async\Task;

use Innmind\Async\{
    Suspension,
    Wait,
};
use Innmind\Signals\Async\Interceptor;
use Innmind\TimeContinuum\Clock;

/**
 * Waiting for IO to be ready or halt to be finished
 *
 * @internal
 */
final class Suspended
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private \Fiber $fiber,
        private Interceptor $interceptor,
        private Suspension $suspension,
    ) {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(
        \Fiber $fiber,
        Interceptor $interceptor,
        Suspension $suspension,
    ): self {
        return new self(
            $fiber,
            $interceptor,
            $suspension,
        );
    }

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
                $this->fiber,
                $this->interceptor,
                $next,
            );
        }

        return Resumable::of(
            $this->fiber,
            $this->interceptor,
            $next,
        );
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
