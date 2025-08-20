<?php
declare(strict_types = 1);

use Innmind\Async\{
    Scheduler,
    Task,
};
use Innmind\OperatingSystem\Factory;
use Innmind\TimeContinuum\Period;
use Innmind\Filesystem\Name;
use Innmind\Http\{
    Request,
    Method,
    ProtocolVersion,
};
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Immutable\Sequence;
use Innmind\BlackBox\Set;

return static function() {
    yield test(
        'Halting multiple tasks',
        static function($assert) {
            $expect = $assert->time(static function() {
                Scheduler::of(Factory::build())
                    ->sink(null)
                    ->with(
                        static fn($_, $__, $continuation) => $continuation
                            ->schedule(Sequence::of(
                                static fn($os) => $os->process()->halt(Period::second(1))->unwrap(),
                                static fn($os) => $os->process()->halt(Period::second(1))->unwrap(),
                                static fn($os) => $os->process()->halt(Period::second(1))->unwrap(),
                            ))
                            ->terminate(),
                    );
            });
            $expect
                ->inLessThan()
                ->seconds(2);
            $expect
                ->inMoreThan()
                ->seconds(1);
        },
    );

    yield proof(
        'Carry value via the scope',
        given(
            Set::type(),
            Set::type(),
        ),
        static function($assert, $initial, $modified) {
            $returned = Scheduler::of(Factory::build())
                ->sink($initial)
                ->with(static fn($_, $__, $continuation) => $continuation->terminate());
            $assert->same($initial, $returned);

            $returned = Scheduler::of(Factory::build())
                ->sink($initial)
                ->with(
                    static fn($carry, $__, $continuation) => $continuation
                        ->carryWith($initial)
                        ->terminate(),
                );
            $assert->same($initial, $returned);

            $returned = Scheduler::of(Factory::build())
                ->sink($initial)
                ->with(
                    static fn($carry, $__, $continuation) => $continuation
                        ->carryWith($modified)
                        ->terminate(),
                );
            $assert->same($modified, $returned);
        },
    );

    yield proof(
        'Retrieve the tasks results',
        given(
            Set::type(),
        ),
        static function($assert, $value) {
            $values = Scheduler::of(Factory::build())
                ->sink(Sequence::of())
                ->with(
                    static fn($all, $__, $continuation, $results) => $continuation
                        ->schedule(match ([$all->size(), $results->size()]) {
                            [0, 0] => Sequence::of(static fn() => $value),
                            default => Sequence::of(),
                        })
                        ->carryWith($all->append($results))
                        ->wakeOnResult(),
                );
            $assert->same([$value], $values->toList());
        },
    );

    yield test(
        'The scope is run asynchronously',
        static function($assert) {
            $expect = $assert->time(static function() {
                Scheduler::of(Factory::build())
                    ->sink(false)
                    ->with(
                        static fn($started, $os, $continuation, $results) => match ([$started, $results->size()]) {
                            [false, 0] => $continuation
                                ->schedule(Sequence::of(
                                    static function($os) {
                                        $os->process()->halt(Period::second(1))->unwrap();
                                        $os->process()->halt(Period::second(1))->unwrap();
                                    },
                                    static function($os) {
                                        $os->process()->halt(Period::second(1))->unwrap();
                                        $os->process()->halt(Period::second(1))->unwrap();
                                    },
                                    static function($os) {
                                        $os->process()->halt(Period::second(1))->unwrap();
                                        $os->process()->halt(Period::second(1))->unwrap();
                                    },
                                ))
                                ->carryWith(true),
                            [true, 0] => (static function($os, $continuation) {
                                // this halt is executed at the same time at the
                                // second one in each task
                                $os->process()->halt(Period::second(1))->unwrap();

                                return $continuation;
                            })($os, $continuation),
                            default => $continuation->terminate(),
                        },
                    );
            });
            $expect
                ->inLessThan()
                ->seconds(3);
            $expect
                ->inMoreThan()
                ->seconds(2);
        },
    );

    yield test(
        'The scope and tasks are run asynchronously',
        static function($assert) {
            $expect = $assert->time(static function() use ($assert) {
                $results = [];
                Scheduler::of(Factory::build())
                    ->sink(false)
                    ->with(
                        static function($started, $os, $continuation) use ($assert, &$results) {
                            if ($started) {
                                $os
                                    ->process()
                                    ->halt(Period::second(2))
                                    ->unwrap();
                                $results[] = 'scope';

                                return $continuation->terminate();
                            }

                            return $continuation
                                ->carryWith(true)
                                ->schedule(Sequence::of(
                                    static function($os) use (&$results) {
                                        // This task halts for 4 seconds because
                                        // if less then it may sometime finish
                                        // before the scope. (as 3-1 ~= 2s)
                                        $os
                                            ->process()
                                            ->halt(Period::second(4))
                                            ->unwrap();
                                        $results[] = 'task 1';
                                    },
                                    static function($os) use (&$results) {
                                        $os
                                            ->process()
                                            ->halt(Period::second(1))
                                            ->unwrap();
                                        $results[] = 'task 2';
                                    },
                                ));
                        },
                    );
                $assert->same(
                    ['task 2', 'scope', 'task 1'],
                    $results,
                );
            });
            $expect
                ->inLessThan()
                ->seconds(5);
            $expect
                ->inMoreThan()
                ->seconds(2);
        },
    );

    yield test(
        'The scope and tasks are run asynchronously in different order',
        static function($assert) {
            $expect = $assert->time(static function() use ($assert) {
                $results = [];
                Scheduler::of(Factory::build())
                    ->sink(false)
                    ->with(
                        static function($started, $os, $continuation) use ($assert, &$results) {
                            if ($started) {
                                $os
                                    ->process()
                                    ->halt(Period::second(3))
                                    ->unwrap();
                                $os
                                    ->process()
                                    ->halt(Period::second(1))
                                    ->unwrap();
                                $results[] = 'scope';

                                return $continuation->terminate();
                            }

                            return $continuation
                                ->carryWith(true)
                                ->schedule(Sequence::of(
                                    static function($os) use (&$results) {
                                        $os
                                            ->process()
                                            ->halt(Period::second(2))
                                            ->unwrap();
                                        $results[] = 'task 1';
                                    },
                                    static function($os) use (&$results) {
                                        $os
                                            ->process()
                                            ->halt(Period::second(1))
                                            ->unwrap();
                                        $results[] = 'task 2';
                                    },
                                ));
                        },
                    );
                $assert->same(
                    ['task 2', 'task 1', 'scope'],
                    $results,
                );
            });
            $expect
                ->inLessThan()
                ->seconds(5);
            $expect
                ->inMoreThan()
                ->seconds(2);
        },
    );

    yield test(
        'Streams read by lines are handled asynchronously',
        static function($assert) {
            $lines = [];
            Scheduler::of(Factory::build())
                ->sink(null)
                ->with(
                    static function($_, $__, $continuation) use ($assert, &$lines) {
                        return $continuation
                            ->schedule(Sequence::of(
                                static function($os) use ($assert, &$lines) {
                                    $file = $os
                                        ->filesystem()
                                        ->mount(Path::of('./'))
                                        ->unwrap()
                                        ->get(Name::of('composer.json'))
                                        ->match(
                                            static fn($file) => $file,
                                            static fn() => null,
                                        );
                                    $assert->not()->null($file);
                                    $lines[] = $file
                                        ->content()
                                        ->lines()
                                        ->first()
                                        ->match(
                                            static fn($line) => $line->toString(),
                                            static fn() => null,
                                        );
                                    $lines[] = $file
                                        ->content()
                                        ->lines()
                                        ->filter(static fn($line) => !$line->str()->empty())
                                        ->last()
                                        ->match(
                                            static fn($line) => $line->toString(),
                                            static fn() => null,
                                        );
                                },
                                static function($os) use ($assert, &$lines) {
                                    $file = $os
                                        ->filesystem()
                                        ->mount(Path::of('./'))
                                        ->unwrap()
                                        ->get(Name::of('LICENSE'))
                                        ->match(
                                            static fn($file) => $file,
                                            static fn() => null,
                                        );
                                    $assert->not()->null($file);
                                    $lines[] = $file
                                        ->content()
                                        ->lines()
                                        ->first()
                                        ->match(
                                            static fn($line) => $line->toString(),
                                            static fn() => null,
                                        );
                                    $lines[] = $file
                                        ->content()
                                        ->lines()
                                        ->filter(static fn($line) => !$line->str()->empty())
                                        ->last()
                                        ->match(
                                            static fn($line) => $line->toString(),
                                            static fn() => null,
                                        );
                                },
                            ))
                            ->terminate();
                    },
                );
            $assert->same(
                ['{', 'MIT License', 'SOFTWARE.', '}'],
                $lines,
            );
        },
    );

    yield test(
        'Streams read by chunks are handled asynchronously',
        static function($assert) {
            $chunks = [];
            Scheduler::of(Factory::build())
                ->sink(null)
                ->with(
                    static function($_, $__, $continuation) use ($assert, &$chunks) {
                        return $continuation
                            ->schedule(Sequence::of(
                                static function($os) use ($assert, &$chunks) {
                                    $file = $os
                                        ->filesystem()
                                        ->mount(Path::of('./'))
                                        ->unwrap()
                                        ->get(Name::of('composer.lock'))
                                        ->match(
                                            static fn($file) => $file,
                                            static fn() => null,
                                        );
                                    $assert->not()->null($file);
                                    $chunks[] = $file
                                        ->content()
                                        ->chunks()
                                        ->last()
                                        ->match(
                                            static fn($chunk) => $chunk->takeEnd(5)->toString(),
                                            static fn() => null,
                                        );
                                },
                                static function($os) use ($assert, &$chunks) {
                                    $file = $os
                                        ->filesystem()
                                        ->mount(Path::of('./'))
                                        ->unwrap()
                                        ->get(Name::of('LICENSE'))
                                        ->match(
                                            static fn($file) => $file,
                                            static fn() => null,
                                        );
                                    $assert->not()->null($file);
                                    $chunks[] = $file
                                        ->content()
                                        ->chunks()
                                        ->last()
                                        ->match(
                                            static fn($chunk) => $chunk->takeEnd(5)->toString(),
                                            static fn() => null,
                                        );
                                },
                            ))
                            ->terminate();
                    },
                );
            // since the license file is shorter it finishes first even though
            // it started after reading the composer.lock file thus showing the
            // chunks are read asynchronously
            $assert->same(
                ["ARE.\n", "0\"\n}\n"],
                $chunks,
            );
        },
    );

    yield test(
        'HTTP requests are handled asynchronously',
        static function($assert) {
            $order = [];
            Scheduler::of(Factory::build())
                ->sink(null)
                ->with(
                    static function($_, $__, $continuation) use ($assert, &$order) {
                        return $continuation
                            ->schedule(Sequence::of(
                                static function($os) use ($assert, &$order) {
                                    $os
                                        ->remote()
                                        ->http()(Request::of(
                                            Url::of('https://httpbun.org/delay/2'),
                                            Method::get,
                                            ProtocolVersion::v11,
                                        ))
                                        ->match(
                                            static fn() => null,
                                            static fn() => null,
                                        );
                                    $order[] = 'first';
                                },
                                static function($os) use ($assert, &$order) {
                                    $os
                                        ->remote()
                                        ->http()(Request::of(
                                            Url::of('https://httpbun.org/delay/1'),
                                            Method::get,
                                            ProtocolVersion::v11,
                                        ))
                                        ->match(
                                            static fn() => null,
                                            static fn() => null,
                                        );
                                    $order[] = 'second';
                                },
                            ))
                            ->terminate();
                    },
                );

            $assert->same(
                ['second', 'first'],
                $order,
            );
        },
    );

    yield test(
        'Discard results',
        static function($assert) {
            $results = Scheduler::of(Factory::build())
                ->sink(Sequence::of())
                ->with(
                    static fn($all, $__, $continuation, $results) => $continuation
                        ->schedule(Sequence::of(
                            static fn($os) => $os->process()->halt(Period::second(1))->unwrap(),
                            static fn($os) => $os->process()->halt(Period::second(1))->unwrap(),
                            static fn($os) => $os->process()->halt(Period::second(1))->unwrap(),
                        )->map(Task\Discard::result(...)))
                        ->carryWith($all->append($results))
                        ->wakeOnResult(),
                );

            $assert->count(0, $results);
        },
    );

    yield proof(
        'Limit concurrency',
        given(
            Set::integers()->between(2, 10),
            Set::integers()->between(2, 10),
        ),
        static function($assert, $tasks, $max) {
            $assert
                ->time(static function() use ($tasks, $max) {
                    Scheduler::of(Factory::build())
                        ->limitConcurrencyTo($max)
                        ->sink(null)
                        ->with(
                            static fn($_, $__, $continuation) => $continuation
                                ->schedule(
                                    Sequence::of()->pad(
                                        $tasks,
                                        static fn($os) => $os
                                            ->process()
                                            ->halt(Period::second(1))
                                            ->unwrap(),
                                    ),
                                )
                                ->terminate(),
                        );
                })
                ->inLessThan()
                ->seconds(
                    // +1 as the system itself takes a bit of time to run
                    ((int) \ceil($tasks / $max)) + 1,
                );
        },
    );
};
