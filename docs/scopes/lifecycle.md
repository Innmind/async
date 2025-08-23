# Lifecycle

## Restart the scope

By default the `$continuation` object that the scope needs to return is configured to tell the scheduler to call the scope once again. This is why be default the scheduler runs an infinite loop.

When you return a continuation with scheduled tasks, the scheduler will start these tasks immediately and call the scope once again.

!!! warning ""
    This means that if your scope never waits on anything (1) before scheduling tasks you'll enter in a runaway situation. New tasks will keep piling on and the scheduler will never resume the suspended tasks.
    {.annotate}

    1. Either by halting the process or watching for sockets/files.

    You **must** always wait before scheduling new tasks and restarting the scope, even if it's halting the process for 1 microsend.

## Carry a value

Since a scope acts as a reducer, it can keep track of a carried value that will be returned by the scheduler once the scope finishes or terminate.

```php title="Scope.php" hl_lines="4"
final class Scope
{
    public function __invoke(
        mixed $carriedValue, // <-- this is the value
        OperatingSystem $os,
        Continuation $continuation,
    ): Continuation {
        return $continuation;
    }
}
```

Each time the scope is called it receives the carried value from the previous call. On the first call it will receive the value passed as argument to `#!php $scheduler->sink($carriedValue)`.

You can change the carried value for the next call via `#!php $continuation->carryWith($newValue)`;

## Run tasks in the background

If you want to run multiple tasks asynchronously but you don't care about there results, you need to tell the scheduler to not call the scope after scheduling the tasks.

```php title="Scope.php" hl_lines="10"
final class Scope
{
    public function __invoke(
        $_,
        OperatingSystem $os,
        Continuation $continuation,
    ): Continuation {
        return $continuation
            ->schedule($tasks)
            ->finish();
    }
}
```

## Wait to start tasks

In most cases your scope will wait for some external event before scheduling a new task. Such event can be receiving an incoming connection on a socket:

```php
use Innmind\Url\Authority\Port;
use Innmind\IO\Sockets\{
    Servers\Server,
    Internet\Transport,
};
use Innmind\IP\IPv4;
use Innmind\TimeContinuum\Period;

final class Scope
{
    private ?Server $server = null;

    public function __invoke(
        $_,
        OperatingSystem $os,
        Continuation $continuation,
    ): Continuation {
        $server = $this->server ??= $os
            ->ports()
            ->open(Transport::tcp(), IPv4::localhost(), Port::of(8080))
            ->unwrap();
        $tasks = $server
            ->timeoutAfter(Period::second(1))
            ->accept()
            ->map(static fn($client) => new Task($client))
            ->maybe()
            ->toSequence();

        return $continuation->schedule($tasks);
    }
}
```

This will look for an incoming connection every second.

Even though we wait for an incoming connection it doesn't block other tasks because the scope itself is run asynchronously.

## Wait for tasks results

Once you've scheduled all your tasks, you can tell the scheduler to call the scope only when tasks results are available. You can do that with the `#!php $continuation->wakeOnResult()` method.

```php title="Scope.php"
use Innmind\Immutable\Sequence;

final class Scope
{
    private bool $scheduled;

    /**
     * @param Sequence<mixed> $results
     */
    public function __invoke(
        $_,
        OperatingSystem $os,
        Continuation $continuation,
        Sequence $results,
    ): Continuation {
        if (!$this->scheduled) {
            $this->scheduled = true,

            return $continuation
                ->schedule($tasks)
                ->wakeOnResult();
        }

        doSomething($results);

        return $continuation->wakeOnResult();
    }
}
```

Beware, not all results will be available at once. The scope may be called multiple times.

## Terminate the tasks

If for some reason you need to cancel all the scheduled tasks you can do it with `#!php $continuation->terminate()`. This will make sure the scope is never called again, [send a signal](../tasks/graceful-shutdown.md) to each task and wait for them to stop.

When all tasks finished, the scheduler will return the last carried value.

```php title="Scope.php"
final class Scope
{
    public function __invoke(
        $_,
        OperatingSystem $os,
        Continuation $continuation,
    ): Continuation {
        return $continuation->terminate();
    }
}
```
