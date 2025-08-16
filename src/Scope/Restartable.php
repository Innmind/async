<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

/**
 * Scope call has finished but asked to call it again
 */
final class Restartable
{
    private function __construct(
    ) {
    }

    public function __invoke(): Suspended|self|Wakeable|Terminated
    {
    }

    public static function of(): self
    {
        return new self;
    }
}
