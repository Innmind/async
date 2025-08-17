<?php
declare(strict_types = 1);

namespace Innmind\Async;

use Innmind\TimeContinuum\Clock;
use Innmind\IO\Internal\{
    Async\Suspended,
    Watch,
    Watch\Ready,
};
use Innmind\Immutable\Attempt;

/**
 * @psalm-immutable
 */
final class Suspension
{
    private function __construct(
        private Suspended $kind,
    ) {
    }

    /**
     * @psalm-pure
     */
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

    /**
     * @param Attempt<Ready> $ready
     */
    public function next(
        Clock $clock,
        Attempt $ready,
    ): self|Resumption {
        $next = $this->kind->next(
            $clock,
            $ready,
        );

        if ($next instanceof Suspended) {
            return new self($next);
        }

        return Resumption::of($next);
    }

    public function watch(): Watch
    {
        return $this->kind->watch();
    }
}
