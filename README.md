# async

[![CI](https://github.com/Innmind/async/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/Innmind/async/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/innmind/async/branch/develop/graph/badge.svg)](https://codecov.io/gh/innmind/async)
[![Type Coverage](https://shepherd.dev/github/innmind/async/coverage.svg)](https://shepherd.dev/github/innmind/async)

Abstraction on top of `Fiber`s to coordinate multiple tasks asynchronously.

The goal is to easily move the execution of any code built using [`innmind/operating-system`](https://packagist.org/packages/innmind/operating-system) from a synchronous context to an async one. This means that it's easier to experiment running a piece of code asynchronously and then move back if the experiment is not successful. This also means that you can test each part of an asynchronous system synchronously.

## Installation

```sh
composer require innmind/async
```

## Usage

```php
use Innmind\Async\{
    Scheduler,
    Scope\Continuation,
};
use Innmind\OperatingSystem\{
    Factory,
    OperatingSystem,
};
use Innmind\Filesystem\Name;
use Innmind\HttpTransport\Success;
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

[$users] = Scheduler::of(Factory::build());
    ->sink([0, 0, false])
    ->with(
        static function(array $carry, OperatingSystem $os, Continuation $continuation, Sequence $results): Continuation {
            [$users, $finished, $launched] = $carry;

            if (!$launched) {
                return $continuation
                    ->carryWith([$users, $finished, true])
                    ->schedule(Sequence::of(
                        static fn(OperatingSystem $os): int => $os
                            ->remote()
                            ->http()(Request::of(
                                Url::of('http://some-service.tld/users/count'),
                                Method::get,
                                ProtocolVersion::v11,
                            ))
                            ->map(static fn(Success $success): string => $success->response()->body()->toString())
                            ->match(
                                static fn(string $response): int => (int) $response,
                                static fn() => throw new \RuntimeException('Failed to count the users'),
                            ),
                        static fn(OperatingSystem $os): int => $os
                            ->filesystem()
                            ->mount(Path::of('some/directory/'))
                            ->unwrap()
                            ->get(Name::of('users.csv'))
                            ->map(static fn($file) => $file->content()->lines())
                            ->match(
                                static fn(Sequence $lines) => $lines->reduce(
                                    0,
                                    static fn(int $total): int => $total + 1,
                                ),
                                static fn() => throw new \RuntimeException('Users file not found'),
                            ),
                    ));
            }

            $finished += $results->size();
            $users = $results->reduce(
                $users,
                static fn(int $total, int $result): int => $total + $result,
            );
            $continuation = $continuation->carryWith([$users, $finished, $launched]);

            if ($finished === 2) {
                $continuation = $continuation->terminate();
            }

            return $continuation->wakeOnResult();
        },
    );
```

This example counts a number of `$users` coming from 2 sources.

The `Scheduler` object behaves as a _reduce_ operation, that's why it has 2 arguments: a carried value and a reducer (called a scope in this package).

The carried value here is an array that holds the number of fetched users, the number of finished tasks and whether it already launched the tasks or not.

The scope will launch 2 tasks if not already done; the first one does an HTTP call and the second one counts the number of lines in a file. The scope will be called again once a task finishes and their results will be available inside the fourth argument `$results`, it will add the number of finished tasks and the number of users to the carried value array. If both tasks are finished then the scope calls `$continuation->terminate()` to instruct the loop to stop.

When the scope calls `->terminate()` and that all tasks are finished then `->with()` returns the carried value. Here it will assign the aggregation of both tasks results to the value `$users`.

> [!NOTE]
> As long as you use the `$os` abstraction passed as arguments the system will automatically suspend your code when necessary. This means that you don't even need to think about it.

> [!NOTE]
> The scope `callable` is also run asynchronously. This means that you can use it to build a socket server and wait indefinitely for new connections without impacting the execution of already started tasks.

> [!WARNING]
> Do NOT return the `$os` variable outside of the tasks or the scope as it may break your code.

> [!NOTE]
> Since this package has been designed by only passing arguments (no global state) it means that you can compose the use of `Scheduler`, this means that you can run a new instance of `Scheduler` inside a task and it will behave transparently. (Although this feature as not been tested yet!)

## Limitations

### Signals

Signals like `SIGINT`, `SIGTERM`, etc... that are normally handled via `$os->process()->signals()` is not yet supported. This may result in unwanted behaviours.

### HTTP calls

Currently HTTP calls are done via `curl` but it can't be integrated in the same loop as other streams. To allow the coordination of multiple tasks when doing HTTP calls the system use a timeout of `10ms` and switches between tasks at this max rate.

To fix this limitation a new implementation entirely based on PHP streams needs to be created.

Meanwhile if your goal is to make multiple concurrent HTTP calls you don't need this package. [`innmind/http-transport`](https://innmind.org/documentation/getting-started/concurrency/http/) already support concurrent calls on it's own (without the limitation mentionned above).

### SQL queries

SQL queries executed via `$os->remote()->sql()` are still executed synchronously.

To fix this limitation a new implementation entirely based on PHP streams needs to be created.

### Number of tasks

It seems that the current implementation of this package has a [limit of around 100K concurrent tasks](https://bsky.app/profile/baptouuuu.bsky.social/post/3lwr7pei2ek2f) before it starts slowing down drastically.

A simple script scheduling 100k tasks that each halts the process for 10 second will take ~13s.
