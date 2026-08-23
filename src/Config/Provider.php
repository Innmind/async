<?php
declare(strict_types = 1);

namespace Innmind\Async\Config;

use Innmind\Signals\Async\Interceptor;

/**
 * @internal
 * @psalm-immutable
 */
final class Provider
{
    private function __construct(
    ) {
    }

    public function __invoke(?Interceptor $interceptor = null): Async
    {
        return Async::of($interceptor);
    }

    /**
     * @psalm-pure
     */
    public static function new(): self
    {
        return new self;
    }
}
