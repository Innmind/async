<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

/**
 * Scope asked to abort all tasks and will wait for them to finish
 *
 * @internal
 * @psalm-immutable
 * @template C
 */
final class Terminated
{
    /**
     * @param C $carry
     */
    private function __construct(
        private mixed $carry,
    ) {
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param A $carry
     *
     * @return self<A>
     */
    #[\NoDiscard]
    public static function of(mixed $carry): self
    {
        return new self($carry);
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
