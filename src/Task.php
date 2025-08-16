<?php
declare(strict_types = 1);

namespace Innmind\Async;

final class Task
{
    private function __construct(
    ) {
    }

    public static function of(callable $task): self
    {
        return new self;
    }
}
