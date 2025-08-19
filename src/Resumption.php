<?php
declare(strict_types = 1);

namespace Innmind\Async;

use Innmind\TimeWarp\Async\Resumable as Halt;
use Innmind\IO\Internal\Async\Resumable as IO;

/**
 * @internal
 * @psalm-immutable
 */
final class Resumption
{
    private function __construct(
        private IO|Halt $kind,
    ) {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(IO|Halt $kind): self
    {
        return new self($kind);
    }

    #[\NoDiscard]
    public function unwrap(): IO|Halt
    {
        return $this->kind;
    }
}
