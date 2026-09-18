<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient;

use haddowg\JsonApiClient\Exceptions\TransportException;
use haddowg\JsonApiClient\Http\Discovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * How a client reaches its server.
 *
 * ```php
 * new ClientOptions(
 *     baseUrl: 'https://api.example.com',
 *     headers: fn () => ['Authorization' => 'Bearer ' . $tokens->fresh()],
 * );
 * ```
 *
 * The header provider is a callable resolved on **every** request, not a header map captured
 * once. That is the whole point of it: a bearer token that expires mid-process is refreshed by
 * the provider, and the client never has to be rebuilt. The framework bridges wire this to the
 * container rather than being the only place it exists, so a standalone user gets the same
 * behaviour without hand-rolling token refresh around every call.
 *
 * The transport is a PSR-18 client, which is what lets an application keep one HTTP stack:
 * Symfony's `Psr18Client`, Laravel's HTTP client adapted, Guzzle, a recording fake in tests.
 * Leave it out and an installed implementation is discovered.
 */
final class ClientOptions
{
    /**
     * Resolved per request; see the class docblock.
     *
     * @var (\Closure(): array<string, string>)|null
     */
    private readonly ?\Closure $headers;

    /**
     * @param string                                   $baseUrl prefixed to every relative path; may
     *                                                          carry a path of its own
     * @param (callable(): array<string, string>)|null $headers resolved per request
     */
    public function __construct(
        public readonly string $baseUrl,
        private readonly ?ClientInterface $transport = null,
        ?callable $headers = null,
        private readonly ?RequestFactoryInterface $requestFactory = null,
        private readonly ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->headers = $headers === null ? null : $headers(...);
    }

    /**
     * @throws TransportException when no PSR-18 client was given and none could be discovered
     */
    public function transport(): ClientInterface
    {
        return $this->transport
            ?? Discovery::httpClient()
            ?? throw TransportException::noHttpClient();
    }

    /**
     * @throws TransportException when no PSR-17 request factory was given and none could be discovered
     */
    public function requestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory
            ?? Discovery::requestFactory()
            ?? throw TransportException::noHttpFactory('request factory');
    }

    /**
     * @throws TransportException when no PSR-17 stream factory was given and none could be discovered
     */
    public function streamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory
            ?? Discovery::streamFactory()
            ?? throw TransportException::noHttpFactory('stream factory');
    }

    /**
     * Resolve the per-request headers. Empty when no provider was given.
     *
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers === null ? [] : ($this->headers)();
    }

    /**
     * A copy sending its requests somewhere else, keeping the transport and header provider.
     */
    public function withBaseUrl(string $baseUrl): self
    {
        return new self(
            $baseUrl,
            $this->transport,
            $this->headers,
            $this->requestFactory,
            $this->streamFactory,
        );
    }
}
