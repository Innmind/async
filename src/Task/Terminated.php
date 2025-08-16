<?php
declare(strict_types = 1);

namespace Innmind\Async\Task;

/**
 * Task should be disposed
 */
final class Terminated
{
    private function __construct(
        private mixed $returned,
    ) {
    }

    public static function of(mixed $returned): self
    {
        return new self($returned);
    }

    public function next(): self
    {
        return $this;
    }

    public function returned(): mixed
    {
        return $this->returned;
    }
}
