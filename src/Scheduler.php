<?php
declare(strict_types = 1);

namespace Innmind\Async;

use Innmind\Async\Scheduler\Sink;
use Innmind\OperatingSystem\OperatingSystem;

final class Scheduler
{
    private function __construct(
        private OperatingSystem $sync,
    ) {
    }

    public static function of(OperatingSystem $os): self
    {
        return new self($os);
    }

    /**
     * @template C
     *
     * @param C $carry
     *
     * @return Sink<C>
     */
    public function sink(mixed $carry): Sink
    {
        return Sink::of($this->sync, $carry);
    }
}
