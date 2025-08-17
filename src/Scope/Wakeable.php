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
 * Scope call has finished but asked to call it again once tasks result are
 * available
 * @template C
 */
final class Wakeable
{
    /**
     * @psalm-mutation-free
     *
     * @param Sequence<callable> $tasks
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
     * @return pure-callable(Sequence<callable>, A): self<A>
     */
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
     * @return Suspended<C>|Restartable<C>|self<C>|Terminated<C>
     */
    public function next(
        OperatingSystem $async,
        Sequence $results,
    ): Suspended|Restartable|self|Terminated {
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
            Restartable::of($this->scope),
            self::of($this->scope),
            Terminated::of(...),
        );
    }

    /**
     * @return Terminated<C>
     */
    public function terminate(): Terminated
    {
        return Terminated::of(
            $this->tasks->clear(),
            $this->carry,
        );
    }

    /**
     * @psalm-mutation-free
     *
     * @return Sequence<callable>
     */
    public function tasks(): Sequence
    {
        return $this->tasks;
    }
}
