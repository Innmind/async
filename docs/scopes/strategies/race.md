# Race for a result

=== "Scheduler"
    ```php
    use Innmind\Async\Scheduler;
    use Innmind\OperatingSystem\Factory;

    $result = Scheduler::of(Factory::build())
        ->sink(null)
        ->with(new Scope);
    $result === 'foo'; // true
    ```

    This is always `foo` because it's the task that waits the less.

=== "Scope"
    ```php
    use Innmind\Async\Scope\Continuation;
    use Innmind\OperatingSystem\OperatingSystem;
    use Innmind\TimeContinuum\Period;
    use Innmind\Immutable\Sequence;

    final class Scope
    {
        private bool $scheduled;

        public function __invoke(
            array $results,
            OperatingSystem $os,
            Continuation $continuation,
            Sequence $newResults,
        ): Continuation {
            if (!$this->scheduled) {
                $this->scheduled = true;

                return $continuation
                    ->schedule(Sequence::of(
                        static fn($os) => $os
                            ->process()
                            ->halt(Period::second(2))
                            ->map(static fn() => 'bar')
                            ->unwrap(),
                        static fn($os) => $os
                            ->process()
                            ->halt(Period::second(1))
                            ->map(static fn() => 'foo')
                            ->unwrap(),
                    ))
                    ->wakeOnResult();
            }

            return $results->first()->match(
                static fn($value) => $continuation
                    ->carryWith($value)
                    ->finish(),
                static fn() => $continuation->wakeOnResult(),
            );
        }
    }
    ```

