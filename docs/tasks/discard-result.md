# Discard result

When a task finishes the returned value (1) will be sent to the scope the next time it's called. But if you don't need to handle the result value this adds an overhead as the results are collected and the scope needs to be called.
{.annotate}

1. Even if it's `#!php null`.

You can avoid this overhead by returning a special object that tells the scheduler to ignore the value.

```php title="Task.php"
use Innmind\Async\Task\Discard;
use Innmind\OperatingSystem\OperatingSystem;

final class Task
{
    public function __invoke(OperatingSystem $os): Discard
    {
        // do something

        return Discard::result;
    }
}
```

Or if you don't want your task to be aware of that but want this logic to be held by your scope it's as simple as:

```php
use Innmind\Async\{
    Scope\Continuation,
    Task\Discard,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

final class Scope
{
    public function __invoke(
        $_,
        OperatingSystem $os,
        Continuation $continuation,
    ): Continuation {
        return $continuation->schedule(Sequence::of(
            Discard::result(new Task),
        ));
    }
}
```
