<?php
declare(strict_types = 1);

namespace Innmind\Async\Loop;

/**
 * @psalm-immutable
 * @template C
 */
final class Continuation
{
    /**
     * @param C $carry
     */
    private function __construct(
        private mixed $carry,
    ) {
    }

    /**
     * @template A
     *
     * @param A $carry
     *
     * @return self<A>
     */
    public static function of(mixed $carry): self
    {
        return new self($carry);
    }
}
