<?php
declare(strict_types = 1);

namespace Innmind\Async;

use Innmind\IO\Internal\Async\Resumable;

/**
 * @psalm-immutable
 */
final class Resumption
{
    private function __construct(
        private Resumable $kind,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(Resumable $kind): self
    {
        return new self($kind);
    }

    public function unwrap(): Resumable
    {
        return $this->kind;
    }
}
