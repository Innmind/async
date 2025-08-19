<?php
declare(strict_types = 1);

namespace Innmind\Async\Wait;

use Innmind\IO\Internal\Watch\Ready;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

/**
 * @internal
 * @psalm-immutable
 */
final class IO
{
    /**
     * @param Attempt<Ready> $ready
     */
    private function __construct(
        private Attempt $ready,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param Attempt<Ready> $ready
     */
    #[\NoDiscard]
    public static function of(Attempt $ready): self
    {
        return new self($ready);
    }

    #[\NoDiscard]
    public function toTime(): Time
    {
        return Time::of(
            $this->ready->map(SideEffect::identity(...)),
        );
    }

    /**
     * @return Attempt<Ready>
     */
    #[\NoDiscard]
    public function unwrap(): Attempt
    {
        return $this->ready;
    }
}
