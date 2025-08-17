<?php
declare(strict_types = 1);

namespace Innmind\Async\Wait;

use Innmind\IO\Internal\Watch\Ready;
use Innmind\Immutable\Attempt;

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
     * @param Attempt<Ready> $ready
     */
    public static function of(Attempt $ready): self
    {
        return new self($ready);
    }

    /**
     * @return Attempt<Ready>
     */
    public function unwrap(): Attempt
    {
        return $this->ready;
    }
}
