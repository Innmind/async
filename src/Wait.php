<?php
declare(strict_types = 1);

namespace Innmind\Async;

final class Wait
{
    private function __construct(
    ) {
    }

    /**
     * @param Sequence<Task\Suspended> $tasks
     *
     * @return array{
     *     Scope\Suspended|Scope\Resumable,
     *     Sequence<Task\Suspendend|Task\Resumable>,
     * }
     */
    public function __invoke(
        Scope\Suspended $scope,
        Sequence $tasks,
    ): array {
    }

    public static function new(): self
    {
        return new self;
    }
}
