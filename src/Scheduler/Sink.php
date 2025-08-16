<?php
declare(strict_types = 1);

namespace Innmind\Async\Scheduler;

final class Sink
{
    private function __construct(
        private mixed $carry,
    ) {
    }

    public static function of(mixed $carry): self
    {
        return new self($carry);
    }

    public function with(callable $scope): mixed
    {
        return $this->carry;
    }
}
