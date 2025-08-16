<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

use Innmind\Async\Scope\Continuation\Next;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 * @todo carry template
 */
final class Continuation
{
    /**
     * @param Sequence<callable> $tasks
     */
    private function __construct(
        private Next $next,
        private Sequence $tasks,
        private mixed $carry,
    ) {
    }

    /**
     * @psalm-pure
     * @internal
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
     * @param Sequence<callable> $tasks
     */
    #[\NoDiscard]
    public function schedule(Sequence $tasks): self
    {
        // Use ->prepend() to let the caller use lazy sequences
        return new self(
            $this->next,
            $tasks->prepend($this->tasks),
            $this->carry,
        );
    }

    #[\NoDiscard]
    public function terminate(): self
    {
        return new self(
            Next::terminate,
            $this->tasks,
            $this->carry,
        );
    }

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
     * @internal
     * @template A
     * @template B
     * @template C
     *
     * @param pure-callable(Sequence<callable>, mixed): A $restart
     * @param pure-callable(Sequence<callable>, mixed): B $wake
     * @param pure-callable(Sequence<callable>, mixed): C $terminate
     *
     * @return A|B|C
     */
    #[\NoDiscard]
    public function match(
        callable $restart,
        callable $wake,
        callable $terminate,
    ): mixed {
        return match ($this->next) {
            Next::restart => $restart($this->tasks, $this->carry),
            Next::wake => $wake($this->tasks, $this->carry),
            Next::terminate => $terminate($this->tasks, $this->carry),
        };
    }
}
