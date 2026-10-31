<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Bridge\Laravel;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Laravel's HTTP client as a PSR-18 transport.
 *
 * Each request is sent through the `Http` factory's own public API, so its middleware,
 * `Http::fake()` and `Http::assertSent()` apply to it as to any other call the application makes,
 * including a fake set up after the client was built. As PSR-18 requires, an error status is
 * returned rather than thrown and redirects are not followed.
 */
final class LaravelTransport implements ClientInterface
{
    public function __construct(private readonly Factory $http) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            if (\strtolower((string) $name) !== 'host') {
                $headers[(string) $name] = \implode(', ', $values);
            }
        }

        $pending = $this->http->createPendingRequest()->withoutRedirecting()->withHeaders($headers);

        $body = (string) $request->getBody();
        if ($body !== '') {
            $pending = $pending->withBody($body, $request->getHeaderLine('Content-Type'));
        }

        try {
            return $pending->send($request->getMethod(), (string) $request->getUri())->toPsrResponse();
        } catch (ConnectionException $e) {
            throw new LaravelNetworkException($request, $e);
        }
    }
}
