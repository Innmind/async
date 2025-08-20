<?php
declare(strict_types = 1);

namespace Innmind\Async\Config;

use Innmind\Signals\Async\Interceptor;
use Innmind\TimeContinuum\Clock;

/**
 * @internal
 * @psalm-immutable
 */
final class Provider
{
    private function __construct(
        private Clock $clock,
    ) {
    }

    public function __invoke(?Interceptor $interceptor = null): Async
    {
        return Async::of($this->clock, $interceptor);
    }

    /**
     * @psalm-pure
     */
    public static function of(Clock $clock): self
    {
        return new self($clock);
    }
}
