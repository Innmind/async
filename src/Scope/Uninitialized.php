<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

final class Uninitialized
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
