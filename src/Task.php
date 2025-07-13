<?php
declare(strict_types = 1);

namespace Innmind\Async;

use Innmind\OperatingSystem\OperatingSystem;

final class Task
{
    /**
     * @param \Closure(OperatingSystem): mixed $task
     */
    private function __construct(
        private \Closure $task,
    ) {
    }

    /**
     * @param callable(OperatingSystem): mixed $task
     */
    public static function of(callable $task): self
    {
        return new self(\Closure::fromCallable($task));
    }
}
