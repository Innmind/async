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
 *
 * @internal
 * @template C
 */
final class Wakeable
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
     * @return Suspended<C>|Restartable<C>|self<C>|Aborted<C>|Terminated<C>
     */
    #[\NoDiscard]
    public function next(
        OperatingSystem $async,
        Sequence $results,
    ): Suspended|Restartable|self|Aborted|Terminated {
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
            Aborted::of(...),
            Terminated::of(...),
        );
    }

    /**
     * @return self<C>
     */
    public function clear(): self
    {
        return new self(
            $this->scope,
            $this->tasks->clear(),
            $this->carry,
        );
    }

    /**
     * @return Terminated<C>
     */
    #[\NoDiscard]
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
     * @return Sequence<callable(OperatingSystem)>
     */
    #[\NoDiscard]
    public function tasks(): Sequence
    {
        return $this->tasks;
    }
}
