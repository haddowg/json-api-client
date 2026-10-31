<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Bridge\Symfony;

use haddowg\JsonApiClient\Bridge\ClientConfig;
use haddowg\JsonApiClient\ClientOptions;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * The generated clients an application has configured under `json_api_client.clients`.
 *
 * Each client is also a service of its own class, so this is mostly what the bundle builds them
 * through and what the console commands read.
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
     * @param array<string, array<array-key, mixed>> $clients         the `clients` configuration
     * @param ContainerInterface|null                $headerProviders invokable header providers by client name
     *
     * @throws \InvalidArgumentException when a client is misconfigured
     */
    public function __construct(
        array $clients,
        private readonly string $default,
        private readonly ?ClientInterface $transport = null,
        private readonly ?RequestFactoryInterface $requestFactory = null,
        private readonly ?StreamFactoryInterface $streamFactory = null,
        private readonly ?ContainerInterface $headerProviders = null,
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

        if (isset($this->clients[$name])) {
            return $this->clients[$name];
        }

        $config = $this->config($name);
        $class = $config->client;

        if (!\class_exists($class)) {
            throw new \InvalidArgumentException(\sprintf('The JSON:API client "%s" names %s, which does not exist. Run `bin/console json-api-client:generate %s`, and register the generated autoload.php in composer.json.', $name, $class, $name));
        }

        return $this->clients[$name] = new $class($this->options($name));
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
        return $this->config($name)->options($this->transport, $this->requestFactory, $this->streamFactory, $this->headers($name));
    }

    public function transport(): ?ClientInterface
    {
        return $this->transport;
    }

    public function requestFactory(): ?RequestFactoryInterface
    {
        return $this->requestFactory;
    }

    /**
     * @return (\Closure(): array<string, string>)|null
     */
    private function headers(string $name): ?\Closure
    {
        if ($this->headerProviders === null || !$this->headerProviders->has($name)) {
            return null;
        }

        $provider = $this->headerProviders->get($name);
        if (!\is_callable($provider)) {
            throw new \InvalidArgumentException(\sprintf('The header provider of the JSON:API client "%s" must be invokable.', $name));
        }

        return static function () use ($provider): array {
            $headers = $provider();
            if (!\is_array($headers)) {
                throw new \UnexpectedValueException('A JSON:API client header provider must return an array of header names to values.');
            }

            $out = [];
            foreach ($headers as $header => $value) {
                $out[(string) $header] = \is_scalar($value) ? (string) $value : throw new \UnexpectedValueException(\sprintf('The "%s" header must be a string.', $header));
            }

            return $out;
        };
    }
}
