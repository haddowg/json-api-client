<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient;

use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\JsonApiErrorResponse;
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
 * Because those headers go on every request, the client only follows a URL the server hands back,
 * such as a pagination link, when it is on the base URL's origin (scheme, host and port). One
 * pointing anywhere else throws {@see Exceptions\CrossOriginRequestException} and nothing is sent.
 *
 * The transport is a PSR-18 client, which is what lets an application keep one HTTP stack:
 * Symfony's `Psr18Client`, Laravel's HTTP client adapted, Guzzle, a recording fake in tests.
 * Leave it out and an installed implementation is discovered.
 *
 * `$errorCodes` maps error codes this API adds to the classes generated for them. The runtime
 * already knows every code in core's catalogue, so a generated client passes only its own here.
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
     * @param array<string, class-string<CodedErrorResponse&JsonApiErrorResponse>> $errorCodes
     */
    public function __construct(
        public readonly string $baseUrl,
        private readonly ?ClientInterface $transport = null,
        ?callable $headers = null,
        private readonly ?RequestFactoryInterface $requestFactory = null,
        private readonly ?StreamFactoryInterface $streamFactory = null,
        private readonly array $errorCodes = [],
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
     * The error codes this API adds, mapped to their classes.
     *
     * @return array<string, class-string<CodedErrorResponse&JsonApiErrorResponse>>
     */
    public function errorCodes(): array
    {
        return $this->errorCodes;
    }

    /**
     * A copy sending its requests somewhere else, keeping everything else.
     */
    public function withBaseUrl(string $baseUrl): self
    {
        return new self(
            $baseUrl,
            $this->transport,
            $this->headers,
            $this->requestFactory,
            $this->streamFactory,
            $this->errorCodes,
        );
    }

    /**
     * A copy that also maps `$codes` to their classes, as a generated client does with the codes
     * its API adds. A later mapping for the same code wins.
     *
     * @param array<string, class-string<CodedErrorResponse&JsonApiErrorResponse>> $codes
     */
    public function withErrorCodes(array $codes): self
    {
        return new self(
            $this->baseUrl,
            $this->transport,
            $this->headers,
            $this->requestFactory,
            $this->streamFactory,
            [...$this->errorCodes, ...$codes],
        );
    }
}
