<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Bridge\Laravel;

use Illuminate\Http\Client\ConnectionException;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Laravel's connection failure, as the PSR-18 network exception the runtime turns into a
 * `TransportException`.
 *
 * @internal thrown by {@see LaravelTransport}
 */
final class LaravelNetworkException extends \RuntimeException implements NetworkExceptionInterface
{
    public function __construct(private readonly RequestInterface $request, ConnectionException $previous)
    {
        parent::__construct($previous->getMessage(), 0, $previous);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
