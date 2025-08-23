# Create a task

Let's build a simple task that looks in a database if a user exists and if not inserts it.

```php title="Task.php"
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Url\Url;
use Formal\AccessLayer\Query\{
    SQL,
    Parameter,
};

final class Task
{
    public function __construct(
        private string $user,
    ) {
    }

    public function __invoke(OperatingSystem $os): void
    {
        $database = $os
            ->remote()
            ->sql(Url::of('mysql://user:password@127.0.0.1/database'));

        $existing = $database(
            SQL::of('SELECT * FROM users WHERE name = ?')->with(Parameter::of(
                $this->user,
            )),
        );

        if (!$existing->empty()) {
            return;
        }

        $database(
            SQL::of('INSERT INTO users (name) VALUES (?)')->with(Parameter::of(
                $this->user,
            )),
        );
    }
}
```

You can now schedule your tasks:

```php title="Scope.php"
use Innmind\Async\Scope\Continuation;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

final class Scope
{
    public function __invoke(
        mixed $_,
        OperatingSystem $os,
        Continuation $continuation,
    ): Continuation {
        $users = $this->fetch($os);

        return $continuation
            ->schedule(
                Sequence::of(...$users)
                    ->map(static fn(string $user) => new Task($user)),
            )
            ->finish();
    }

    /**
     * @return list<string>
     */
    private function fetch(OperatingSystem $os): array
    {
        // see previous chapter
    }
}
```
