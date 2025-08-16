<?php
declare(strict_types = 1);

namespace Innmind\Async\Scheduler;

use Innmind\Async\{
    Scope,
    Scope\Continuation,
    Wait,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

final class Sink
{
    private function __construct(
        private OperatingSystem $sync,
        private mixed $carry,
    ) {
    }

    public static function of(
        OperatingSystem $sync,
        mixed $carry,
    ): self {
        return new self($sync, $carry);
    }

    /**
     * @param callable(mixed, OperatingSystem, Continuation, Sequence<mixed>): Continuation $scope
     */
    public function with(callable $scope): mixed
    {
        $state = State::new(Scope::of(
            $scope,
            $this->carry,
        ));

        do {
            [$state, $terminated] = $state
                ->next($this->sync)
                ->wait(Wait::new());
        } while (\is_null($terminated));

        return $terminated->carry();
    }
}
