<?php
declare(strict_types = 1);

namespace Innmind\Async;

use Innmind\IO\Internal\Async\Suspended;

final class Suspension
{
    private function __construct(
        private Suspended $kind,
    ) {
    }

    public static function of(mixed $kind): ?self
    {
        if (\is_null($kind)) {
            return null;
        }

        if ($kind instanceof Suspended) {
            return new self($kind);
        }

        throw new \LogicException('Unknown kind of suspension');
    }
}
