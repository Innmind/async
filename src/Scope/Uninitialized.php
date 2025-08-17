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
 * @template C
 */
final class Uninitialized
{
    /**
     * @psalm-mutation-free
     *
     * @param C $carry
     */
    private function __construct(
        private Scope $scope,
        private mixed $carry,
    ) {
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param A $carry
     *
     * @return self<A>
     */
    public static function of(Scope $scope, mixed $carry): self
    {
        return new self($scope, $carry);
    }

    /**
     * @return Suspended<C>|Restartable<C>|Wakeable<C>|Terminated<C>
     */
    public function next(OperatingSystem $async): Suspended|Restartable|Wakeable|Terminated
    {
        $fiber = $this->scope->new();
        $return = Suspension::of($fiber->start(
            $this->carry,
            $async,
            Continuation::new($this->carry),
            Sequence::of(), // no results
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
            Wakeable::of($this->scope),
            Terminated::of(...),
        );
    }
}
