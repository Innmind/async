<?php
declare(strict_types = 1);

namespace Innmind\Async\Wait;

use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

/**
 * @internal
 * @psalm-immutable
 */
final class Time
{
    /**
     * @param Attempt<SideEffect> $result
     */
    private function __construct(
        private Attempt $result,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param Attempt<SideEffect> $result
     */
    #[\NoDiscard]
    public static function of(Attempt $result): self
    {
        return new self($result);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function unwrap(): Attempt
    {
        return $this->result;
    }
}
