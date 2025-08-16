<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

/**
 * IO is ready or halt is finished
 */
final class Resumable
{
    private function __construct(
    ) {
    }

    public function __invoke(): Suspended|Restartable|Wakeable|Terminated
    {
    }

    public static function of(): self
    {
        return new self;
    }
}
