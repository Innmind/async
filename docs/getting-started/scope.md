# Create a scope

As mentionned in the [preface](../preface/terminology.md#scope) a Scope is a function. In fact, it can be any `callable` that accept the following arguments:

<div class="annotate" markdown>

- a carried value (1)
- an instance of the operating system
- a continuation
- a list of results

</div>

1. You'll learn in a [later chapter](../scopes/lifecycle.md#carry-a-value) how to use this value.

To keep things simple for now, we'll only talk about the second and third arguments. And to feel a bit more at home we'll use a class with the `__invoke` method instead of an anonymous function.

If we re-implement the example from the previous page we get:

=== "Scope"
    ```php title="Scope.php"
    use Innmind\Async\Scope\Continuation;
    use Innmind\OperatingSystem\OperatingSystem;

    final class Scope
    {
        public function __invoke(
            mixed $_,
            OperatingSystem $os,
            Continuation $continuation,
        ): Continuation {
            return $continuation;
        }
    }
    ```

=== "Scheduler"
    ```php title="async.php"
    <?php
    declare(strict_types = 1);

    require 'path/to/vendor/autoload.php';

    use Innmind\Async\{
        Scheduler,
        Scope\Continuation,
    };
    use Innmind\OperatingSystem\{
        Factory,
        OperatingSystem,
    };

    $os = Factory::build();
    Scheduler::of($os)
        ->sink(null)
        ->with(new Scope);
    ```

Once again, if you run `php async.php` in your terminal it will execute an infinite loop that does nothing.

However by defining the scope with an object we see that the `__invoke` method will always be called on the same object. This means you can keep state inside properties if you want to!

Now let's actually do something in this scope.

The most basic thing you can do is halting the process:

```php title="Scope.php" hl_lines="3 12-15"
use Innmind\Async\Scope\Continuation;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\TimeContinuum\Period;

final class Scope
{
    public function __invoke(
        mixed $_,
        OperatingSystem $os,
        Continuation $continuation,
    ): Continuation {
        $os
            ->process()
            ->halt(Period::second(1))
            ->unwrap();

        return $continuation;
    }
}
```

This will pause the scope for a second before returning the continuation. And since by default a continuation instruct the system to call the scope once again, this means that the `__invoke` method is called every second.

Since we only work with the scope for now, this is the same as doing:

```php
do {
    sleep(1);
} while (true);
```

Now that we have a timer, we can do something else every second. For example we can fetch data via an HTTP call:

```php title="Scope.php" hl_lines="3-9 23-38"
use Innmind\Async\Scope\Continuation;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\HttpTransport\Success;
use Innmind\Http\{
    Request,
    Method,
    ProtocolVersion,
};
use Innmind\Url\Url;
use Innmind\TimeContinuum\Period;

final class Scope
{
    public function __invoke(
        mixed $_,
        OperatingSystem $os,
        Continuation $continuation,
    ): Continuation {
        $os
            ->process()
            ->halt(Period::second(1))
            ->unwrap();
        $users = $os
            ->remote()
            ->http()(
                Request::of(
                    Url::of('https://somewhere.tld/api/users'),
                    Method::get,
                    ProtocolVersion::v11,
                ),
            )
            ->match(
                static fn(Success $success) => \json_decode(
                    $success->response()->body()->toString(),
                    true,
                ),
                static fn(object $error) => throw new \RuntimeException('An error occured'),
            );

        return $continuation;
    }
}
```

Now every second we call an api to fetch users and decode the response content. Putting everything inside the `__invoke` method can become quite verbose. But since we're in a class we can create other methods:

```php title="Scope.php" hl_lines="19 24-46"
use Innmind\Async\Scope\Continuation;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\HttpTransport\Success;
use Innmind\Http\{
    Request,
    Method,
    ProtocolVersion,
};
use Innmind\Url\Url;
use Innmind\TimeContinuum\Period;

final class Scope
{
    public function __invoke(
        mixed $_,
        OperatingSystem $os,
        Continuation $continuation,
    ): Continuation {
        $users = $this->fetch($os);

        return $continuation;
    }

    /**
     * @return list<string>
     */
    private function fetch(OperatingSystem $os): array
    {
        $os
            ->process()
            ->halt(Period::second(1))
            ->unwrap();
        $users = $os
            ->remote()
            ->http()(
                Request::of(
                    Url::of('https://somewhere.tld/api/users'),
                    Method::get,
                    ProtocolVersion::v11,
                ),
            )
            ->match(
                static fn(Success $success) => \json_decode(
                    $success->response()->body()->toString(),
                    true,
                ),
                static fn(object $error) => throw new \RuntimeException('An error occured'),
            );
    }
}
```

We'll see in the next chapter how to run tasks for each of these users.

!!! tip
    You should explore the other APIs provided by the [operating system](https://innmind.org/OperatingSystem/).
