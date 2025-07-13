<?php
declare(strict_types = 1);

namespace Innmind\Async\Loop;

use Innmind\Async\{
    Loop\Continuation\State,
    Task,
};
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 * @template C
 */
final class Continuation
{
    /**
     * @param C $carry
     * @param Sequence<Task> $tasks
     */
    private function __construct(
        private mixed $carry,
        private State $state,
        private Sequence $tasks,
    ) {
    }

    /**
     * @internal
     * @template A
     *
     * @param A $carry
     *
     * @return self<A>
     */
    public static function of(mixed $carry): self
    {
        return new self($carry, State::resume, Sequence::of());
    }

    /**
     * @param C $carry
     *
     * @return self<C>
     */
    public function carryWith(mixed $carry): self
    {
        return new self($carry, $this->state, $this->tasks);
    }

    /**
     * @param Sequence<Task> $tasks
     *
     * @return self<C>
     */
    public function launch(Sequence $tasks): self
    {
        // Use ->prepend() to let the caller use lazy sequences
        return new self(
            $this->carry,
            $this->state,
            $tasks->prepend($this->tasks),
        );
    }

    /**
     * This means that any task result will be discarded
     *
     * @return self<C>
     */
    public function terminate(): self
    {
        return new self(
            $this->carry,
            State::terminate,
            $this->tasks,
        );
    }

    /**
     * @return self<C>
     */
    public function wakeOnResult(): self
    {
        return new self(
            $this->carry,
            State::wakeOnResult,
            $this->tasks,
        );
    }

    /**
     * @internal
     * @template R1
     * @template R2
     * @template R3
     *
     * @param callable(C, Sequence<Task>): R1 $resume
     * @param callable(C, Sequence<Task>): R2 $terminate
     * @param callable(C, Sequence<Task>): R3 $wakeOnResult
     *
     * @return R1|R2|R3
     */
    public function match(
        callable $resume,
        callable $terminate,
        callable $wakeOnResult,
    ): mixed {
        /** @psalm-suppress ImpureFunctionCall */
        return match ($this->state) {
            State::resume => $resume($this->carry, $this->tasks),
            State::terminate => $terminate($this->carry, $this->tasks),
            State::wakeOnResult => $wakeOnResult($this->carry, $this->tasks),
        };
    }
}
