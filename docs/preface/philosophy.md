# Philosophy

Usually you'll want to use asynchronous code to improve the performance of your application. Async allows to fix IO performance. When doing IO (network calls and such) a good amount of time is spent waiting for the network to respond. Instead of waiting, async allows to do other things.

This means you'll try to fix problems in an existing codebase.

The goal of this package is to allow you to try running your existing synchronous code asynchronously without changing your implementation. This has 3 big advantages:

<div class="annotate" markdown>

- you can experiment with async with your _real_ code (instead of a proof of concept)
- you can go back if the async experiment isn't conclusive (1)
- you can run your code asynchronously and test it synchronously (2)

</div>

1. Thus being more cost effective for your company.
2. Which is usually a major pain point of async code.

Unlike other async packages, instead of trying to duplicate the PHP functions in order to make then run asynchronously, this package rely on higher level abstractions. This helps reduce the amount of code necessary to make this package possible and consequently reduce the maintainability cost.

## Abstraction

This package relies on the [`innmind/operating-system`](https://innmind.org/OperatingSystem/) abstraction. It offers all the APIs that could benefit from being run asynchronously.

Since all these APIs are accessed through a single object it offers a simple way to move a code from synchronous to asynchronous.

The APIs concerned are:

- halting the process (aka an abstraction on top of `#!php sleep`)
- sockets (HTTP, SQL, etc...)
- files
- processes

By focusing on this abstraction as the central point to make a code run asynchronously brings another advantage. Any abstraction built on top of it makes it automatically async compatible. No need to use differents ecosystems (sync vs async).

All this is completely transparent thanks to a lower level abstraction: Monads.

## Monads

Monads are data structures coming from functional programming. They help solve different use cases. But the common point between all of them is that you describe what you want to do and not how to do it. It's this particular point that allows a system to inject logic on the _how_ part to make the code run asynchronously. Without you being aware of it.

The other big advantage of monads is their great composability. Because you only control _what_ you want to do you can safely build abstractions upon abstractions without breaking the sync (or async) nature of the code.

All the Innmind ecosystem rely of the monads provided by [`innmind/immutable`](https://innmind.org/Immutable/).

## Pooling suspensions

The use of the operating system abstraction in the end describe only 2 ways a code should be suspended: (1)
{.annotate}

1. aka instruct to do something else while waiting.

- waiting X amount of time
- watching for `resource`s to be ready (to read/write)

The goal of _pooling_ these suspensions is to determine the shortest amount of time the process really need to wait before it can again do something.

## MapReduce

[MapReduce](https://en.wikipedia.org/wiki/MapReduce) is a pattern with 2 components: Map and Reduce.

- A Map describes tasks that can be safely done concurrently to produce a result.
- A Reduce describes the way to aggregate the result of multiple tasks to a new result.

This pattern is great because it's simple to grasp and is composable. Indeed a Map operation could itself use the MapReduce pattern to compute its value.

This package is designed around this pattern, even though it uses a different terminology. It's composed of:

- a [Scope](terminology.md#scope) acting as a Reduce
    - it creates tasks
    - it computes a [carried value](terminology.md#carried-value)
- a [Task](terminology.md#task) acting as a Map
    - it computes a value that is fed back to the scope
