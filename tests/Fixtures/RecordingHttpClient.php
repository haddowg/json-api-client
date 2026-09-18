<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A PSR-18 client that answers from a queue and keeps what it was asked.
 */
final class RecordingHttpClient implements ClientInterface
{
    /**
     * @var list<RequestInterface>
     */
    public array $requests = [];

    /**
     * @var list<ClientExceptionInterface|ResponseInterface>
     */
    private array $queue;

    public function __construct(ClientExceptionInterface|ResponseInterface ...$queue)
    {
        $this->queue = \array_values($queue);
    }

    /**
     * @param array<string, string> $headers
     */
    public static function answering(
        int $status = 200,
        string $body = '',
        array $headers = ['Content-Type' => 'application/vnd.api+json'],
    ): self {
        return new self(new Response($status, $headers, $body));
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        $next = \array_shift($this->queue);

        if ($next === null) {
            return new Response(204);
        }

        if ($next instanceof ClientExceptionInterface) {
            throw $next;
        }

        return $next;
    }

    public function lastRequest(): RequestInterface
    {
        $last = $this->requests[\count($this->requests) - 1] ?? null;

        if ($last === null) {
            throw new \LogicException('Nothing was sent.');
        }

        return $last;
    }
}
