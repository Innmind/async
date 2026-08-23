<?php
declare(strict_types = 1);

namespace Innmind\Async\Config;

use Innmind\OperatingSystem\Config;
use Innmind\Signals\Async\Interceptor;
use Innmind\Time\{
    Clock,
    Halt,
};

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
        // todo add $config->mapIO()
        $io = $config->io()->asAsync($this->clock);
        // todo add $config->mapSignals()
        $signals = $config->signalsHandler()->asAsync(
            $this->interceptor,
        );

        return $config
            ->withIO($io)
            ->mapHalt(fn() => Halt::async($this->clock))
            ->mapHttpTransport(fn($transport, $config) => $transport->map(
                fn($http) => $http->asAsync(
                    $this->clock,
                    $config->halt(),
                    $config->io(),
                ),
            ))
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
