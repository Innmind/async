<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

/**
 * Scope call has finished but asked to call it again once tasks result are
 * available
 */
final class Wakeable
{
    private function __construct(
    ) {
    }

    public function __invoke(): Suspended|Restartable|self|Terminated
    {
    }

    public static function of(): self
    {
        return new self;
    }
}
