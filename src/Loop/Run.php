<?php
declare(strict_types = 1);

namespace Innmind\Async\Loop;

use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Attempt;

/**
 * @template C
 */
final class Run
{
    /**
     * @param C $carry
     */
    private function __construct(
        private OperatingSystem $os,
        private mixed $carry,
    ) {
    }

    /**
     * @param callable(C, OperatingSystem, Continuation<C>): Continuation<C> $source
     *
     * @return Attempt<C>
     */
    public function __invoke(callable $source): Attempt
    {
        return Attempt::result($this->carry);
    }

    /**
     * @template A
     *
     * @param A $carry
     *
     * @return self<A>
     */
    public static function of(OperatingSystem $os, mixed $carry): self
    {
        return new self($os, $carry);
    }
}
