# Gather results

=== "Scheduler"
    ```php
    use Innmind\Async\Scheduler;
    use Innmind\OperatingSystem\Factory;

    $results = Scheduler::of(Factory::build())
        ->sink([])
        ->with(new Scope);
    $results === ['foo' => 'bar', 'bar' => 'baz']; // true
    ```

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
                        static fn($os) => [
                            'foo' => $os
                                ->process()
                                ->halt(Period::second(1))
                                ->map(static fn() => 'bar')
                                ->unwrap(),
                        ],
                        static fn($os) => [
                            'bar' => $os
                                ->process()
                                ->halt(Period::second(2))
                                ->map(static fn() => 'baz')
                                ->unwrap(),
                        ],
                    ))
                    ->wakeOnResult();
            }

            return $continuation
                ->carryWith(\array_merge(
                    $results,
                    ...$newResults->toList(),
                ))
                ->wakeOnResult();
        }
    }
    ```
