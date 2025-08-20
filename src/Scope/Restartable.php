<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

use Innmind\Async\{
    Scope,
    Suspension,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

/**
 * Scope call has finished but asked to call it again
 *
 * @internal
 * @template C
 */
final class Restartable
{
    /**
     * @psalm-mutation-free
     *
     * @param Sequence<callable(OperatingSystem)> $tasks
     * @param C $carry
     */
    private function __construct(
        private Scope $scope,
        private Sequence $tasks,
        private mixed $carry,
    ) {
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @return pure-callable(Sequence<callable(OperatingSystem)>, A): self<A>
     */
    #[\NoDiscard]
    public static function of(Scope $scope): callable
    {
        return static fn(Sequence $tasks, mixed $carry) => new self(
            $scope,
            $tasks,
            $carry,
        );
    }

    /**
     * @param Sequence<mixed> $results
     *
     * @return Suspended<C>|self<C>|Wakeable<C>|Aborted<C>|Terminated<C>
     */
    #[\NoDiscard]
    public function next(
        OperatingSystem $async,
        Sequence $results,
    ): Suspended|self|Wakeable|Aborted|Terminated {
        $fiber = $this->scope->new();
        $return = Suspension::of($fiber->start(
            $this->carry,
            $async,
            Continuation::new($this->carry),
            $results,
        ));

        if ($return instanceof Suspension) {
            return Suspended::of(
                $this->scope,
                $fiber,
                $return,
            );
        }

        /** @var Continuation */
        $continuation = $fiber->getReturn();

        return $continuation->match(
            self::of($this->scope),
            Wakeable::of($this->scope),
            Aborted::of(...),
            Terminated::of(...),
        );
    }

    /**
     * @psalm-mutation-free
     *
     * @return Sequence<callable(OperatingSystem)>
     */
    #[\NoDiscard]
    public function tasks(): Sequence
    {
        return $this->tasks;
    }
}
