<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

use Innmind\Async\{
    Scope,
    Suspension,
};

final class Uninitialized
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private Scope $scope,
        private mixed $carry,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(Scope $scope, mixed $carry): self
    {
        return new self($scope, $carry);
    }

    public function next(): Suspended|Restartable|Wakeable|Terminated
    {
        $fiber = $this->scope->new();
        /** @var ?Suspension */
        $return = $fiber->start(Continuation::new($this->carry));

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
