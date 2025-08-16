<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

/**
 * Waiting for IO to be ready or halt to be finished
 */
final class Suspended
{
    private function __construct(
    ) {
    }

    public function __invoke(): self|Resumable
    {
    }

    public static function of(): self
    {
        return new self;
    }
}
