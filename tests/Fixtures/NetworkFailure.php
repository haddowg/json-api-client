<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * What a PSR-18 client throws when the request never reached the server.
 */
final class NetworkFailure extends \RuntimeException implements NetworkExceptionInterface
{
    private ?RequestInterface $request = null;

    public function getRequest(): RequestInterface
    {
        if ($this->request === null) {
            throw new \LogicException('No request was attached to this failure.');
        }

        return $this->request;
    }

    public function for(RequestInterface $request): self
    {
        $this->request = $request;

        return $this;
    }
}
