<?php
declare(strict_types = 1);

namespace Innmind\Async\Task;

final class Uninitialized
{
    private function __construct(
    ) {
    }

    public static function of(): self
    {
        return new self;
    }
}
