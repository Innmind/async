<?php
declare(strict_types = 1);

namespace Innmind\Async;

use Innmind\TimeContinuum\Clock;
use Innmind\IO\Internal\{
    Async\Suspended,
    Watch,
};

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

    public function next(
        Clock $clock,
        Wait\IO $result,
    ): self|Resumption {
        $next = $this->kind->next(
            $clock,
            $result->unwrap(),
        );

        if ($next instanceof Suspended) {
            return new self($next);
        }

        return Resumption::of($next);
    }

    public function fold(?Watch $watch): ? Watch
    {
        $self = $this->kind->watch();

        if (\is_null($watch)) {
            return $self;
        }

        return $watch->merge($self);
    }
}
