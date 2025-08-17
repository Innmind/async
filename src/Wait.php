<?php
declare(strict_types = 1);

namespace Innmind\Async;

use Innmind\OperatingSystem\OperatingSystem;
use Innmind\TimeContinuum\Period;
use Innmind\IO\Internal\Watch;

final class Wait
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private Watch|Period|null $wait,
    ) {
    }

    public function __invoke(OperatingSystem $sync): Wait\IO|Wait\Time|null
    {
        if (\is_null($this->wait)) {
            return null;
        }

        if ($this->wait instanceof Period) {
            return Wait\Time::of($sync->process()->halt($this->wait));
        }

        return Wait\IO::of(($this->wait)());
    }

    public static function nothing(): self
    {
        return new self(null);
    }

    /**
     * @psalm-mutation-free
     */
    #[\NoDiscard]
    public function with(Suspension $suspension): self
    {
        return new self(
            $suspension->fold($this->wait),
        );
    }
}
