<?php
declare(strict_types = 1);

namespace Innmind\Async;

use Innmind\Async\Scheduler\Sink;

final class Scheduler
{
    private function __construct(
    ) {
    }

    public static function of(): self
    {
        return new self;
    }

    public function sink(mixed $carry): Sink
    {
        return Sink::of($carry);
    }
}
