<?php

require 'vendor/autoload.php';

use Innmind\Async\{
    Scheduler,
    Task\Discard,
};
use Innmind\OperatingSystem\{
    Factory,
    OperatingSystem,
};
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\Sequence;

$os = Factory::build();

Scheduler::of($os)
    ->sink(null)
    ->with(
        static fn($_, $__, $cont) => $cont
            ->schedule(Sequence::of()->pad(
                (int) ($argv[1] ?? 100),
                static fn($os) => $os->process()->halt(Period::second(10)),
            ))
            ->finish(),
    );

\printf(
    "%.2f Mo\n",
    memory_get_peak_usage(true)/1024/1024,
);
