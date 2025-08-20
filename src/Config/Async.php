<?php
declare(strict_types = 1);

namespace Innmind\Async\Config;

use Innmind\OperatingSystem\Config;
use Innmind\Signals\Async\Interceptor;
use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use Innmind\HttpTransport\Curl;
use Innmind\TimeWarp\Halt;
use Innmind\IO\IO;

/**
 * @internal
 */
final class Async
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private Clock $clock,
        private ?Interceptor $interceptor,
    ) {
    }

    public function __invoke(Config $config): Config
    {
        $halt = Halt\Async::of($this->clock);
        $io = IO::async($this->clock);
        // todo handle max concurrency + ssl configuration
        // todo build a native client based on innmind/io to better integrate in
        // this system.
        $http = Curl::of(
            $this->clock,
            $io,
        )
            ->heartbeat(
                Period::millisecond(10), // this is blocking the active task so it needs to be low
                static fn() => $halt(Period::millisecond(1))->unwrap(), // this allows to jump between tasks
            );
        $signals = $config
            ->signalsHandler()
            ->async($this->interceptor);

        return $config
            ->haltProcessVia($halt)
            ->useHttpTransport($http)
            ->withIO($io)
            ->handleSignalsVia($signals);
    }

    /**
     * @psalm-pure
     */
    public static function of(Clock $clock, ?Interceptor $interceptor): self
    {
        return new self($clock, $interceptor);
    }
}
