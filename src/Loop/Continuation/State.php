<?php
declare(strict_types = 1);

namespace Innmind\Async\Loop\Continuation;

/**
 * @internal
 * @psalm-immutable
 */
enum State
{
    case resume;
    case terminate;
    case wakeOnResult;
}
