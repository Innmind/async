<?php
declare(strict_types = 1);

namespace Innmind\Async;

use Innmind\IO\Internal\Async\Resumable;

use Innmind\IO\Internal\Watch\Ready;
use Innmind\Immutable\Attempt;

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

    /**
     * @return Attempt<Ready>
     */
    public function unwrap(): Attempt
    {
        return $this->kind->ready();
    }
}
