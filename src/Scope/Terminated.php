<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

/**
 * Scope call has finished and should be disposed
 *
 * @internal
 * @psalm-immutable
 * @template C
 */
final class Terminated
{
    /**
     * @param Sequence<callable(OperatingSystem)> $tasks
     * @param C $carry
     */
    private function __construct(
        private Sequence $tasks,
        private mixed $carry,
    ) {
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param Sequence<callable(OperatingSystem)> $tasks
     * @param A $carry
     *
     * @return self<A>
     */
    #[\NoDiscard]
    public static function of(
        Sequence $tasks,
        mixed $carry,
    ): self {
        return new self($tasks, $carry);
    }

    /**
     * @return self<C>
     */
    #[\NoDiscard]
    public function next(): self
    {
        return new self(
            $this->tasks->clear(),
            $this->carry,
        );
    }

    /**
     * @return Sequence<callable(OperatingSystem)>
     */
    #[\NoDiscard]
    public function tasks(): Sequence
    {
        return $this->tasks;
    }

    /**
     * @return C
     */
    #[\NoDiscard]
    public function carry(): mixed
    {
        return $this->carry;
    }
}
