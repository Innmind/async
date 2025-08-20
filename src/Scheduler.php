<?php
declare(strict_types = 1);

namespace Innmind\Async;

use Innmind\Async\Scheduler\Sink;
use Innmind\OperatingSystem\OperatingSystem;

/**
 * @psalm-immutable
 */
final class Scheduler
{
    /**
     * @param ?int<2, max> $concurrencyLimit
     */
    private function __construct(
        private OperatingSystem $sync,
        private ?int $concurrencyLimit,
    ) {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(OperatingSystem $os): self
    {
        return new self($os, null);
    }

    /**
     * @param int<2, max> $max
     */
    #[\NoDiscard]
    public function limitConcurrencyTo(int $max): self
    {
        return new self(
            $this->sync,
            $max,
        );
    }

    /**
     * @template C
     *
     * @param C $carry
     *
     * @return Sink<C>
     */
    #[\NoDiscard]
    public function sink(mixed $carry): Sink
    {
        return Sink::of($this->sync, $this->concurrencyLimit, $carry);
    }
}
