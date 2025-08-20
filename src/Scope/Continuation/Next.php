<?php
declare(strict_types = 1);

namespace Innmind\Async\Scope\Continuation;

/**
 * @psalm-immutable
 * @internal
 */
enum Next
{
    case restart;
    case wake;
    case abort;
    case finish;
}
