<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

use Innmind\Async\{
    Scope,
    Suspension,
};

/**
 * IO is ready or halt is finished
 * @template C
 */
final class Resumable
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private Scope $scope,
        private \Fiber $fiber,
        private mixed $result, // todo type
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(
        Scope $scope,
        \Fiber $fiber,
        mixed $result, // todo type
    ): self {
        return new self($scope, $fiber, $result);
    }

    /**
     * @return Suspended<C>|Restartable<C>|Wakeable<C>|Terminated<C>
     */
    public function next(): Suspended|Restartable|Wakeable|Terminated
    {
        /** @var ?Suspension */
        $return = $this->fiber->resume($this->result);

        if ($return instanceof Suspension) {
            return Suspended::of(
                $this->scope,
                $this->fiber,
                $return,
            );
        }

        /** @var Continuation */
        $continuation = $this->fiber->getReturn();

        return $continuation->match(
            Restartable::of($this->scope),
            Wakeable::of($this->scope),
            Terminated::of(...),
        );
    }
}
