<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Bridge\Laravel;

use Illuminate\Http\Client\Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Laravel's HTTP client as a PSR-18 transport.
 *
 * Each request goes through a fresh pending request from the `Http` factory, so its middleware,
 * `Http::fake()` and `Http::assertSent()` apply, including a fake set up after the client was
 * built. The options are PSR-18's (no exception on an error status, no redirects) plus the request
 * data Laravel's own fake and recorder expect to find.
 */
final class LaravelTransport implements ClientInterface
{
    public function __construct(private readonly Factory $http) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->http->createPendingRequest()->buildClient()->send($request, [
            'http_errors' => false,
            'allow_redirects' => false,
            'laravel_data' => [],
        ]);
    }
}
