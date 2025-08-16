<?php
declare(strict_types = 1);

namespace Innmind\Async\Task;

/**
 * Task should be disposed
 */
final class Terminated
{
    private function __construct(
    ) {
    }

    public function __invoke(): self
    {
        return $this;
    }

    public static function of(): self
    {
        return new self;
    }
}
