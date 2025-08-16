<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

use Innmind\Immutable\Sequence;

/**
 * Scope call has finished and should be disposed
 * @psalm-immutable
 */
final class Terminated
{
    /**
     * @param Sequence<callable> $tasks
     */
    private function __construct(
        private Sequence $tasks,
        private mixed $carry,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<callable> $tasks
     */
    public static function of(
        Sequence $tasks,
        mixed $carry,
    ): self {
        return new self($tasks, $carry);
    }

    public function next(): self
    {
        return new self(
            $this->tasks->clear(),
            $this->carry,
        );
    }

    /**
     * @return Sequence<callable>
     */
    public function tasks(): Sequence
    {
        return $this->tasks;
    }

    public function carry(): mixed
    {
        return $this->carry;
    }
}
