<?php
declare(strict_types = 1);

namespace Innmind\Async;

use Innmind\Async\Loop\Run;
use Innmind\OperatingSystem\OperatingSystem;

final class Loop
{
    private function __construct(
        private OperatingSystem $os,
    ) {
    }

    /**
     * @template C
     *
     * @param C $carry
     *
     * @return Run<C>
     */
    public function __invoke(mixed $carry): Run
    {
        return Run::of($this->os, $carry);
    }

    public static function of(OperatingSystem $os): self
    {
        return new self($os);
    }
}
