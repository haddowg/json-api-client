<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Bridge;

use haddowg\JsonApiClient\ClientOptions;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * One generated client as a framework bridge configures it: which class it is, where its server
 * is, how it authenticates, and where its OpenAPI document and generated code live.
 *
 * Both bridges read the same keys, so a client is configured the same way in Laravel's
 * `config/json-api-client.php` and in Symfony's `json_api_client:` tree.
 *
 * @internal built by the bridges from their configuration
 */
final class ClientConfig
{
    /**
     * @param string                $client  the generated root client class, which does not exist
     *                                       until the client has been generated
     * @param array<string, string> $headers sent on every request
     * @param string|null           $spec    the OpenAPI document, as a URL or a file path
     * @param string|null           $output  the directory the generated code is committed to
     * @param string|null           $target  the PHP version the code is generated for, `8.3` or `8.4`
     */
    public function __construct(
        public readonly string $name,
        public readonly string $client,
        public readonly string $baseUrl,
        public readonly ?string $token = null,
        public readonly array $headers = [],
        public readonly ?string $spec = null,
        public readonly ?string $output = null,
        public readonly ?string $target = null,
    ) {}

    /**
     * Read one client's configuration, checking each key.
     *
     * @param array<array-key, mixed> $config
     *
     * @throws \InvalidArgumentException when a key is missing or of the wrong type
     */
    public static function fromArray(string $name, array $config): self
    {
        $client = self::string($name, $config, 'client');
        $baseUrl = self::string($name, $config, 'base_url');

        if ($client === null || $baseUrl === null) {
            throw new \InvalidArgumentException(\sprintf('The JSON:API client "%s" needs both "client" and "base_url".', $name));
        }

        $headers = $config['headers'] ?? [];
        if (!\is_array($headers)) {
            throw new \InvalidArgumentException(\sprintf('The "headers" of the JSON:API client "%s" must be a map of header names to values.', $name));
        }

        $map = [];
        foreach ($headers as $header => $value) {
            if (!\is_string($value)) {
                throw new \InvalidArgumentException(\sprintf('The "%s" header of the JSON:API client "%s" must be a string.', $header, $name));
            }
            $map[(string) $header] = $value;
        }

        return new self(
            $name,
            $client,
            $baseUrl,
            self::string($name, $config, 'token'),
            $map,
            self::string($name, $config, 'spec'),
            self::string($name, $config, 'output'),
            self::string($name, $config, 'target'),
        );
    }

    /**
     * The options the generated client is built with. A token becomes a bearer `Authorization`
     * header, and `$headers` is resolved on every request, after the configured headers, so a
     * provider can refresh a token without rebuilding the client.
     *
     * @param (callable(): array<string, string>)|null $headers
     */
    public function options(
        ?ClientInterface $transport = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?callable $headers = null,
    ): ClientOptions {
        $static = $this->headers;
        if ($this->token !== null && $this->token !== '') {
            $static['Authorization'] = 'Bearer ' . $this->token;
        }

        $provider = $headers === null ? null : $headers(...);

        return new ClientOptions(
            baseUrl: $this->baseUrl,
            transport: $transport,
            headers: $static === [] && $provider === null ? null : static fn(): array => [...$static, ...($provider === null ? [] : $provider())],
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
        );
    }

    /**
     * The `--client` name the codegen takes: the class's short name without its `Client` suffix.
     */
    public function clientName(): string
    {
        $short = \substr($this->client, (int) \strrpos('\\' . $this->client, '\\'));

        return \str_ends_with($short, 'Client') && $short !== 'Client' ? \substr($short, 0, -6) : $short;
    }

    /**
     * The namespace the generated code lives in: the client class's own.
     */
    public function namespace(): string
    {
        $at = \strrpos($this->client, '\\');

        return $at === false ? '' : \substr($this->client, 0, $at);
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private static function string(string $name, array $config, string $key): ?string
    {
        $value = $config[$key] ?? null;

        if ($value !== null && !\is_string($value)) {
            throw new \InvalidArgumentException(\sprintf('The "%s" of the JSON:API client "%s" must be a string.', $key, $name));
        }

        return $value;
    }
}
