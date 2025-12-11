<?php
declare(strict_types = 1);

namespace Innmind\Async\Config;

use Innmind\OperatingSystem\Config;
use Innmind\Signals\Async\Interceptor;
use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use Innmind\HttpTransport\Transport;
use Innmind\TimeWarp\Halt;
use Innmind\IO\IO;
use Innmind\Signals\Handler;

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
        $halt = Halt::async($this->clock);
        $io = IO::async(
            $config->io(),
            $this->clock,
        );
        // todo build a native client based on innmind/io to better integrate in
        // this system.
        $http = Transport::async(
            $this->clock,
            $io,
            Period::millisecond(10), // this is blocking the active task so it needs to be low
            static fn() => $halt(Period::millisecond(1))->unwrap(), // this allows to jump between tasks
        );
        $signals = Handler::async(
            $config->signalsHandler(),
            $this->interceptor,
        );

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
