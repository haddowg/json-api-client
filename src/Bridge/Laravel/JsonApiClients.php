<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Bridge\Laravel;

use GuzzleHttp\Psr7\HttpFactory;
use haddowg\JsonApiClient\Bridge\ClientConfig;
use haddowg\JsonApiClient\ClientOptions;
use Illuminate\Http\Client\Factory;

/**
 * The generated clients an application has configured, built over Laravel's own HTTP client.
 *
 * Requests go through `Http`, so `Http::fake()` answers them and `Http::assertSent()` sees them,
 * exactly as it does for the application's other outbound calls.
 */
final class JsonApiClients
{
    /**
     * @var array<string, ClientConfig>
     */
    private array $configs = [];

    /**
     * @var array<string, object>
     */
    private array $clients = [];

    /**
     * @var array<string, \Closure(): array<string, string>>
     */
    private array $headerProviders = [];

    /**
     * @param array<string, array<array-key, mixed>> $clients the `clients` map from the configuration
     *
     * @throws \InvalidArgumentException when a client is misconfigured
     */
    public function __construct(
        private readonly Factory $http,
        array $clients,
        private readonly string $default = 'default',
    ) {
        foreach ($clients as $name => $config) {
            $this->configs[$name] = ClientConfig::fromArray($name, $config);
        }
    }

    /**
     * The client configured under `$name`, or the default one, built once.
     *
     * @throws \InvalidArgumentException when no client has that name
     */
    public function client(?string $name = null): object
    {
        $name ??= $this->default;

        return $this->clients[$name] ??= $this->build($this->config($name));
    }

    /**
     * Resolve headers for the client named `$name` on every request, after its configured
     * `headers` and `token`, so a token that expires can be refreshed without rebuilding it.
     *
     * @param callable(): array<string, string> $headers
     */
    public function headersUsing(string $name, callable $headers): void
    {
        $this->headerProviders[$name] = $headers(...);
        unset($this->clients[$name]);
    }

    /**
     * @throws \InvalidArgumentException when no client has that name
     */
    public function config(string $name): ClientConfig
    {
        return $this->configs[$name] ?? throw new \InvalidArgumentException(\sprintf('No JSON:API client is configured as "%s". Configured: %s.', $name, $this->configs === [] ? 'none' : \implode(', ', \array_keys($this->configs))));
    }

    /**
     * @return array<string, ClientConfig>
     */
    public function configs(): array
    {
        return $this->configs;
    }

    public function options(string $name): ClientOptions
    {
        $factory = new HttpFactory();

        return $this->config($name)->options(
            new LaravelTransport($this->http),
            $factory,
            $factory,
            $this->headerProviders[$name] ?? null,
        );
    }

    private function build(ClientConfig $config): object
    {
        $class = $config->client;

        if (!\class_exists($class)) {
            throw new \InvalidArgumentException(\sprintf('The JSON:API client "%s" names %s, which does not exist. Run `php artisan json-api-client:generate %s`, and register the generated autoload.php in composer.json.', $config->name, $class, $config->name));
        }

        return new $class($this->options($config->name));
    }
}
