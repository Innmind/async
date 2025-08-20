<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

use Innmind\Async\Scope\Continuation\Next;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 * @template C
 */
final class Continuation
{
    /**
     * @param Sequence<callable(OperatingSystem)> $tasks
     * @param C $carry
     */
    private function __construct(
        private Next $next,
        private Sequence $tasks,
        private mixed $carry,
    ) {
    }

    /**
     * @psalm-pure
     * @template A
     * @internal
     *
     * @param A $carry
     *
     * @return self<A>
     */
    #[\NoDiscard]
    public static function new(mixed $carry): self
    {
        return new self(
            Next::restart,
            Sequence::of(),
            $carry,
        );
    }

    /**
     * @param C $carry
     *
     * @return self<C>
     */
    #[\NoDiscard]
    public function carryWith(mixed $carry): self
    {
        return new self(
            $this->next,
            $this->tasks,
            $carry,
        );
    }

    /**
     * @param Sequence<callable(OperatingSystem)> $tasks
     *
     * @return self<C>
     */
    #[\NoDiscard]
    public function schedule(Sequence $tasks): self
    {
        // Use ->prepend() to let the caller use lazy sequences but snap it to
        // avoid rescheduling same tasks multiple times
        return new self(
            $this->next,
            $tasks
                ->prepend($this->tasks)
                ->snap(),
            $this->carry,
        );
    }

    /**
     * @return self<C>
     */
    #[\NoDiscard]
    public function finish(): self
    {
        return new self(
            Next::finish,
            $this->tasks,
            $this->carry,
        );
    }

    /**
     * @return self<C>
     */
    #[\NoDiscard]
    public function wakeOnResult(): self
    {
        return new self(
            Next::wake,
            $this->tasks,
            $this->carry,
        );
    }

    /**
     * This will stop the current scope and send a signal to all tasks in order
     * to terminate.
     *
     * @return self<C>
     */
    #[\NoDiscard]
    public function abort(): self
    {
        return new self(
            Next::abort,
            $this->tasks->clear(),
            $this->carry,
        );
    }

    /**
     * @internal
     * @template T
     * @template U
     * @template V
     * @template W
     *
     * @param pure-callable(Sequence<callable(OperatingSystem)>, C): T $restart
     * @param pure-callable(Sequence<callable(OperatingSystem)>, C): U $wake
     * @param pure-callable(C): V $abort
     * @param pure-callable(Sequence<callable(OperatingSystem)>, C): W $finish
     *
     * @return T|U|V|W
     */
    #[\NoDiscard]
    public function match(
        callable $restart,
        callable $wake,
        callable $abort,
        callable $finish,
    ): mixed {
        return match ($this->next) {
            Next::restart => $restart($this->tasks, $this->carry),
            Next::wake => $wake($this->tasks, $this->carry),
            Next::abort => $abort($this->carry),
            Next::finish => $finish($this->tasks, $this->carry),
        };
    }
}
