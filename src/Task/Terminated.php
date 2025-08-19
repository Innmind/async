<?php
declare(strict_types = 1);

namespace Innmind\Async\Task;

/**
 * Task should be disposed
 *
 * @internal
 * @psalm-immutable
 */
final class Terminated
{
    private function __construct(
        private mixed $returned,
    ) {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(mixed $returned): self
    {
        return new self($returned);
    }

    #[\NoDiscard]
    public function returned(): mixed
    {
        return $this->returned;
    }
}
