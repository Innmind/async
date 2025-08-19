<?php
declare(strict_types = 1);

namespace Innmind\Async\Task;

use Innmind\OperatingSystem\OperatingSystem;

/**
 * @psalm-immutable
 */
enum Discard
{
    case result;

    /**
     * @param callable(OperatingSystem): mixed $task
     *
     * @return callable(OperatingSystem): self
     */
    public static function result(callable $task): callable
    {
        return static function($os) use ($task): self {
            $task($os);

            return self::result;
        };
    }
}
