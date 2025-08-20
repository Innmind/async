<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope;

use Innmind\Async\{
    Scope,
    Suspension,
    Resumption,
};

/**
 * IO is ready or halt is finished
 *
 * @internal
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
        private Resumption $resumption,
    ) {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(
        Scope $scope,
        \Fiber $fiber,
        Resumption $resumption,
    ): self {
        return new self($scope, $fiber, $resumption);
    }

    /**
     * @return Suspended<C>|Restartable<C>|Wakeable<C>|Aborted<C>|Finished<C>
     */
    #[\NoDiscard]
    public function next(): Suspended|Restartable|Wakeable|Aborted|Finished
    {
        $return = Suspension::of($this->fiber->resume(
            $this->resumption->unwrap(),
        ));

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
            Aborted::of(...),
            Finished::of(...),
        );
    }
}
