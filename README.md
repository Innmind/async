# async

[![CI](https://github.com/Innmind/async/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/Innmind/async/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/innmind/async/branch/main/graph/badge.svg)](https://codecov.io/gh/innmind/async)
[![Type Coverage](https://shepherd.dev/github/innmind/async/coverage.svg)](https://shepherd.dev/github/innmind/async)

Abstraction on top of `Fiber`s to coordinate multiple tasks asynchronously.

The goal is to easily move the execution of any code built using [`innmind/operating-system`](https://innmind.org/OperatingSystem/) from a synchronous context to an async one. This means that it's easier to experiment running a piece of code asynchronously and then move back if the experiment is not successful. This also means that you can test each part of an asynchronous system synchronously.

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
    OperatingSystem,
    Factory,
};
use Innmind\Immutable\Sequence;

Scheduler::of(Factory::build())
    ->sink(null)
    ->with(
        static fn(
            $_,
            OperatingSystem $os,
            Continuation $continuation,
        ) => $continuation
            ->schedule(Sequence::of(
                static fn(OperatingSystem $os) => importUsers($os),
                static fn(OperatingSystem $os) => importProducts($os),
            ))
            ->finish(),
    );
```
