<?php
declare(strict_types = 1);

namespace Innmind\Async;

use Innmind\IO\Internal\Watch;

final class Wait
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private ?Watch $watch,
    ) {
    }

    public function __invoke(): ?Wait\IO
    {
        if (\is_null($this->watch)) {
            return null;
        }

        return Wait\IO::of(($this->watch)());
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
            $suspension->fold($this->watch),
        );
    }
}
