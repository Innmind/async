<?php
declare(strict_types = 1);

namespace Innmind\Async\Config;

use Innmind\OperatingSystem\Config;
use Innmind\Signals\Async\Interceptor;

/**
 * @internal
 */
final class Async
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private ?Interceptor $interceptor,
    ) {
    }

    public function __invoke(Config $config): Config
    {
        $interceptor = $this->interceptor;

        return $config
            ->mapIO(static fn($io, $config) => $io->asAsync($config->clock()))
            ->mapHalt(static fn($halt, $config) => $halt->asAsync($config->clock()))
            ->mapHttpTransport(static fn($transport, $config) => $transport->map(
                static fn($http) => $http->asAsync(
                    $config->clock(),
                    $config->halt(),
                    $config->io(),
                ),
            ))
            ->mapSignalsHandler(static fn($signals) => $signals->asAsync($interceptor));
    }

    /**
     * @psalm-pure
     */
    public static function of(?Interceptor $interceptor): self
    {
        return new self($interceptor);
    }
}
