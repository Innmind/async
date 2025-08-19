<?php
declare(strict_types = 1);

namespace Innmind\Async;

use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use Innmind\TimeWarp\Async\Suspended as Halt;
use Innmind\IO\Internal\{
    Async\Suspended as IO,
    Watch,
};

/**
 * @internal
 */
final class Suspension
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private IO|Halt $kind,
    ) {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(mixed $kind): ?self
    {
        if (\is_null($kind)) {
            return null;
        }

        if ($kind instanceof IO || $kind instanceof Halt) {
            return new self($kind);
        }

        throw new \LogicException('Unknown kind of suspension');
    }

    #[\NoDiscard]
    public function next(
        Clock $clock,
        Wait\IO|Wait\Time $result,
    ): self|Resumption {
        if (
            $this->kind instanceof IO &&
            $result instanceof Wait\IO
        ) {
            $next = $this->kind->next(
                $clock,
                $result->unwrap(),
            );
        } else if (
            $this->kind instanceof Halt &&
            $result instanceof Wait\Time
        ) {
            $next = $this->kind->next(
                $clock,
                $result->unwrap(),
            );
        } else if (
            $this->kind instanceof Halt &&
            $result instanceof Wait\IO
        ) {
            $next = $this->kind->next(
                $clock,
                $result->toTime()->unwrap(),
            );
        } else {
            // If this case is reached then the implementation of self::fold()
            // is invalid. If a suspension is of the IO kind then the fold
            // operation must always return a Watch otherwise we would halt the
            // process wwithout looking at the streams readyness.
            throw new \LogicException('Unscheduled IO wait');
        }

        if ($next instanceof IO || $next instanceof Halt) {
            return new self($next);
        }

        return Resumption::of($next);
    }

    /**
     * @psalm-mutation-free
     */
    #[\NoDiscard]
    public function fold(Watch|Period|null $wait): Watch|Period|null
    {
        if (\is_null($wait)) {
            return match (true) {
                $this->kind instanceof IO => $this->kind->watch(),
                $this->kind instanceof Halt => $this->kind->period(),
            };
        }

        if ($wait instanceof Period) {
            return match (true) {
                $this->kind instanceof IO => $this->kind->watch($wait),
                $this->kind instanceof Halt => match (true) {
                    $this
                        ->kind
                        ->period()
                        ->asElapsedPeriod()
                        ->longerThan(
                            $wait->asElapsedPeriod(),
                        ) => $wait,
                    default => $this->kind->period(),
                },
            };
        }

        return match (true) {
            $this->kind instanceof IO => $wait->merge($this->kind->watch()),
            $this->kind instanceof Halt => $wait->merge(
                $wait
                    ->clear() // trick to automatically let the watch choose the shortest timeout
                    ->timeoutAfter($this->kind->period()),
            ),
        };
    }
}
