<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Http;

use haddowg\JsonApiClient\ClientOptions;
use haddowg\JsonApiClient\Exceptions\JsonApiErrorResponse;
use haddowg\JsonApiClient\Exceptions\TransportException;
use haddowg\JsonApiClient\MediaType;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Drives a PSR-18 client: resolves the URL, negotiates the media type, applies the per-request
 * headers, and turns a non-2xx into the exception its status maps to.
 *
 * Content negotiation lives here rather than in generated code, so a request only has to say
 * *which* extensions and profiles it needs. The JSON:API media type, the `ext` and `profile`
 * parameters, and the decision to mirror them onto `Content-Type` when there is a body are all
 * this class's business.
 *
 * Retries are not: that is the transport's job, and an application that wants them configures
 * them on the PSR-18 client it already has.
 */
final class Transport
{
    public function __construct(private readonly ClientOptions $options) {}

    /**
     * @throws JsonApiErrorResponse when the server answers with a non-2xx status
     * @throws TransportException   when no response is received at all
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
            throw JsonApiErrorResponse::fromBody($response->status, $response->body);
        }

        return $response;
    }

    /**
     * Compose the PSR-7 request.
     *
     * Headers are layered, and the order is the contract: the negotiated media type first, the
     * client's header provider over that, and the request's own headers last. So auth can be
     * resolved once for the whole client while a single call still gets the final say.
     */
    public function build(Request $request): RequestInterface
    {
        $negotiated = MediaType::value($request->ext, $request->profiles);

        $psrRequest = $this->options->requestFactory()
            ->createRequest($request->method, $this->url($request->uri))
            ->withHeader('Accept', $request->accept ?? $negotiated);

        if ($request->hasBody()) {
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
     * Resolve a request's URI against the base URL, leaving an absolute one alone — a
     * pagination link the server handed back is already fully addressed.
     */
    private function url(string $uri): string
    {
        if (\preg_match('#^https?://#i', $uri) === 1) {
            return $uri;
        }

        $base = \rtrim($this->options->baseUrl, '/');

        if ($uri === '') {
            return $base;
        }

        return $base . '/' . \ltrim($uri, '/');
    }
}
