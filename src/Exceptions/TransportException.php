<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * The request never produced a response: DNS failure, connection refused, timeout, a PSR-18
 * client that rejected the request outright.
 *
 * The underlying PSR-18 exception is the `previous`, so nothing the HTTP stack reported is
 * lost. Bringing it under {@see JsonApiClientException} means one `catch` covers both a
 * server that answered badly and a server that never answered.
 */
final class TransportException extends \RuntimeException implements JsonApiClientException
{
    private function __construct(
        string $message,
        private readonly ?RequestInterface $request,
        ?\Throwable $previous,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function from(ClientExceptionInterface $previous, RequestInterface $request): self
    {
        return new self(
            \sprintf(
                '%s %s failed before a response was received: %s',
                $request->getMethod(),
                (string) $request->getUri(),
                $previous->getMessage(),
            ),
            $request,
            $previous,
        );
    }

    /**
     * The client has no PSR-18 implementation to send with, and none could be discovered.
     */
    public static function noHttpClient(): self
    {
        return new self(
            'No PSR-18 HTTP client is configured. Pass one as the ClientOptions $transport, '
            . 'or install php-http/discovery alongside a PSR-18 implementation so one can be found.',
            null,
            null,
        );
    }

    /**
     * No PSR-17 factory is available to build the request or its body with.
     */
    public static function noHttpFactory(string $factory): self
    {
        return new self(
            \sprintf(
                'No PSR-17 %s is configured. Pass one to ClientOptions, or install '
                . 'php-http/discovery alongside a PSR-17 implementation so one can be found.',
                $factory,
            ),
            null,
            null,
        );
    }

    /**
     * The request that failed, when the failure happened late enough for one to exist.
     */
    public function request(): ?RequestInterface
    {
        return $this->request;
    }
}
