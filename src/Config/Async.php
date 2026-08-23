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
        return $config
            ->mapIO(fn($io) => $io->asAsync($this->clock))
            ->mapHalt(fn($halt) => $halt->asAsync($this->clock))
            ->mapHttpTransport(fn($transport, $config) => $transport->map(
                fn($http) => $http->asAsync(
                    $this->clock,
                    $config->halt(),
                    $config->io(),
                ),
            ))
            ->mapSignalsHandler(fn($signals) => $signals->asAsync($this->interceptor));
    }

    /**
     * @psalm-pure
     */
    public static function of(Clock $clock, ?Interceptor $interceptor): self
    {
        return new self($clock, $interceptor);
    }
}
