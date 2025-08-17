<?php
declare(strict_types = 1);

namespace Innmind\Async\Wait;

use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

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
     * @param Attempt<SideEffect> $result
     */
    public static function of(Attempt $result): self
    {
        return new self($result);
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function unwrap(): Attempt
    {
        return $this->result;
    }
}
