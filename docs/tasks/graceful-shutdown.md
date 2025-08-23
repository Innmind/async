# Gracefully shutdown

Since a task is a function that can be run synchronously or asynchronously, it uses the same mechanism to know that it needs to gracefully stop what it's doing.

This mechanism is [process signals](https://innmind.org/OperatingSystem/use_cases/signals/).

```php title="Task.php"
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Signals\Signal;

final class Task
{
    public function __invoke(OperatingSystem $os)
    {
        $signaled = false;
        $os
            ->process()
            ->signals()
            ->listen(Signal::terminate, function() use (&$signaled) {
                $signaled = true;
            });

        while (!$signaled) {
            // do something
        }
    }
}
```

The `#!php $signaled` variable will be flipped in 2 cases:

- the PHP process receives the signal (that will be dispatched to all tasks that listened to it)
- the scope calls `#!php $continuation->terminate()`

If the scope asks to terminate then it will send the signal to all tasks. This means that if you call this method in your scope you should add a listener to all your tasks otherwise the system may never terminate.
