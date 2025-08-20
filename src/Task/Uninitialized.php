<?php
declare(strict_types = 1);

namespace Innmind\Async\Task;

use Innmind\Async\{
    Suspension,
    Config,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Signals\Async\Interceptor;

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
        private Interceptor $interceptor,
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
        /** @psalm-suppress ImpureMethodCall Todo fix in innmind/signals */
        return new self(
            \Closure::fromCallable($task),
            Interceptor::new(),
        );
    }

    #[\NoDiscard]
    public function next(
        OperatingSystem $sync,
        Config\Provider $config,
    ): Suspended|Terminated {
        $fiber = new \Fiber($this->task);
        $return = Suspension::of($fiber->start(
            $sync->map($config(
                $this->interceptor,
            )),
        ));

        if ($return instanceof Suspension) {
            return Suspended::of(
                $fiber,
                $this->interceptor,
                $return,
            );
        }

        return Terminated::of($fiber->getReturn());
    }
}
