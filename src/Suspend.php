<?php
declare(strict_types = 1);

namespace Innmind\Async;

final class Suspend
{
    private function __construct(
    ) {
    }

    public function halt(): mixed
    {
        return null;
    }

    public function wait(): mixed
    {
        return null;
    }
}
