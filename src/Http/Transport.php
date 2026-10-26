<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Http;

use haddowg\JsonApiClient\ClientOptions;
use haddowg\JsonApiClient\Exceptions\CrossOriginRequestException;
use haddowg\JsonApiClient\Exceptions\JsonApiErrorResponse;
use haddowg\JsonApiClient\Exceptions\TransportException;
use haddowg\JsonApiClient\MediaType;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Drives a PSR-18 client: resolves the URL, negotiates the media type, applies the per-request
 * headers, and turns a non-2xx into the exception its status maps to.
 *
 * Content negotiation lives here rather than in generated code, so a request only has to say
 * *which* extensions and profiles it needs. The JSON:API media type, the `ext` and `profile`
 * parameters, and the decision to mirror them onto `Content-Type` are all this class's business.
 * `Content-Type` goes on every `POST`, with or without a body, because the server refuses a `POST`
 * that does not name the JSON:API media type; any other method carries it only with a body.
 *
 * Retries are not: that is the transport's job, and an application that wants them configures
 * them on the PSR-18 client it already has.
 */
final class Transport
{
    public function __construct(private readonly ClientOptions $options) {}

    /**
     * @throws JsonApiErrorResponse        when the server answers with a non-2xx status
     * @throws TransportException          when no response is received at all
     * @throws CrossOriginRequestException when the URI points off the base URL's origin
     */
    public function send(Request $request): Response
    {
        $psrRequest = $this->build($request);

        try {
            $psrResponse = $this->options->transport()->sendRequest($psrRequest);
        } catch (ClientExceptionInterface $e) {
            throw TransportException::from($e, $psrRequest);
        }

        $response = Response::fromPsr($psrResponse);

        if (!$response->isSuccessful()) {
            throw JsonApiErrorResponse::fromBody($response->status, $response->body, codes: $this->options->errorCodes());
        }

        return $response;
    }

    /**
     * Compose the PSR-7 request.
     *
     * Headers are layered, and the order is the contract: the negotiated media type first, the
     * client's header provider over that, and the request's own headers last. So auth can be
     * resolved once for the whole client while a single call still gets the final say.
     *
     * @throws CrossOriginRequestException when the URI points off the base URL's origin
     */
    public function build(Request $request): RequestInterface
    {
        $negotiated = MediaType::value($request->ext, $request->profiles);

        $psrRequest = $this->options->requestFactory()
            ->createRequest($request->method, $this->url($request->uri))
            ->withHeader('Accept', $request->accept ?? $negotiated);

        if ($request->hasBody() || $request->method === 'POST') {
            $psrRequest = $psrRequest->withHeader('Content-Type', $request->contentType ?? $negotiated);
        }

        foreach ([...$this->options->headers(), ...$request->headers] as $name => $value) {
            $psrRequest = $psrRequest->withHeader($name, $value);
        }

        $body = $request->body;

        if ($body !== null) {
            $psrRequest = $psrRequest->withBody(
                \is_string($body) ? $this->options->streamFactory()->createStream($body) : $body,
            );
        }

        return $psrRequest;
    }

    /**
     * Wrap a body that has already been read in a stream, for a caller that wants a raw response
     * as one.
     */
    public function stream(string $body): StreamInterface
    {
        return $this->options->streamFactory()->createStream($body);
    }

    /**
     * Resolve a request's URI against the base URL.
     *
     * A relative path is appended to the base URL. An absolute URL, which is what a pagination
     * link the server handed back is, is sent as it is, but only when it is on the base URL's
     * origin: every request carries the client's headers, credentials included, and a link to
     * anywhere else would hand them over. A protocol-relative `//host/…` takes the base URL's
     * scheme and is checked the same way.
     *
     * @throws CrossOriginRequestException when the URI points off the base URL's origin
     */
    private function url(string $uri): string
    {
        // A scheme, or a leading pair of slashes or backslashes, makes the URI name its own host.
        if (\preg_match('#^(?:[a-z][a-z0-9+.\-]*:|[/\\\\]{2}|\\\\)#i', $uri) === 1) {
            if (\str_starts_with($uri, '//')) {
                $uri = (\parse_url($this->options->baseUrl, \PHP_URL_SCHEME) ?? 'https') . ':' . $uri;
            }
            if (!self::sameOrigin($uri, $this->options->baseUrl)) {
                throw CrossOriginRequestException::for($uri, $this->options->baseUrl);
            }

            return $uri;
        }

        $base = \rtrim($this->options->baseUrl, '/');

        if ($uri === '') {
            return $base;
        }

        return $base . '/' . \ltrim($uri, '/');
    }

    /**
     * Whether an absolute URL has the same scheme, host and port as the base URL. A backslash,
     * whitespace or control character anywhere in it, or user info, fails outright: parsers
     * disagree about where the host is in such a URL, and this one need not guess right.
     */
    private static function sameOrigin(string $url, string $baseUrl): bool
    {
        if (\preg_match('#[\\\\\x00-\x20\x7F]#', $url) === 1) {
            return false;
        }

        $origin = self::origin($url);

        return $origin !== null && $origin === self::origin($baseUrl);
    }

    /**
     * @return array{string, string, int}|null scheme, host and port, lowercased, for an http(s) URL without user info
     */
    private static function origin(string $url): ?array
    {
        $parts = \parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $scheme = \strtolower($parts['scheme']);
        if ($scheme !== 'http' && $scheme !== 'https') {
            return null;
        }

        return [$scheme, \strtolower($parts['host']), $parts['port'] ?? ($scheme === 'https' ? 443 : 80)];
    }
}
