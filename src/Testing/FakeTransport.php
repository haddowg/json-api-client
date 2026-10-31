<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Testing;

use haddowg\JsonApiClient\Http\Discovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * A PSR-18 transport for tests: it answers from a queue, keeps every request it was sent, and
 * asserts on them.
 *
 * Swap it in for the real transport (both framework bridges have a `fake()` for this) and a test
 * reads like the requests it expects:
 *
 * ```php
 * $fake->respondWith(200, ['data' => ['type' => 'albums', 'id' => '1', 'attributes' => […]]]);
 *
 * $client->albums->id('1')->get();
 *
 * $fake->assertSent(fn (RequestInterface $r) => $r->getMethod() === 'GET'
 *     && $r->getUri()->getPath() === '/albums/1');
 * ```
 *
 * A request with nothing queued for it is a failure rather than an empty answer, because a test
 * that forgot to queue a response should say so instead of reading a `204` as success.
 */
final class FakeTransport implements ClientInterface
{
    /**
     * @var list<RequestInterface>
     */
    private array $sent = [];

    /**
     * @var list<ClientExceptionInterface|ResponseInterface>
     */
    private array $queue = [];

    private readonly ResponseFactoryInterface $responses;

    private readonly StreamFactoryInterface $streams;

    /**
     * Without factories, installed ones are discovered through `php-http/discovery`.
     *
     * @throws \LogicException when no factory was given and none could be discovered
     */
    public function __construct(?ResponseFactoryInterface $responses = null, ?StreamFactoryInterface $streams = null)
    {
        $this->responses = $responses ?? Discovery::responseFactory() ?? throw new \LogicException('The fake transport needs a PSR-17 response factory. Pass one, or install php-http/discovery and a PSR-7 implementation.');
        $this->streams = $streams ?? Discovery::streamFactory() ?? throw new \LogicException('The fake transport needs a PSR-17 stream factory. Pass one, or install php-http/discovery and a PSR-7 implementation.');
    }

    /**
     * Queue an answer. An array body is encoded as a JSON:API document.
     *
     * @param array<string, mixed>|string $body
     * @param array<string, string>       $headers
     */
    public function respondWith(int $status = 200, array|string $body = '', array $headers = []): self
    {
        if (\is_array($body)) {
            $body = \json_encode($body, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);
            $headers += ['Content-Type' => 'application/vnd.api+json'];
        }

        $response = $this->responses->createResponse($status)->withBody($this->streams->createStream($body));
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $this->queue[] = $response;

        return $this;
    }

    /**
     * Queue a response or a transport failure exactly as given.
     */
    public function push(ClientExceptionInterface|ResponseInterface $answer): self
    {
        $this->queue[] = $answer;

        return $this;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->sent[] = $request;

        $next = \array_shift($this->queue);

        if ($next === null) {
            self::fail(\sprintf('The fake transport has no response queued for %s %s.', $request->getMethod(), (string) $request->getUri()));
        }

        if ($next instanceof ClientExceptionInterface) {
            throw $next;
        }

        return $next;
    }

    /**
     * Every request sent so far, in order.
     *
     * @return list<RequestInterface>
     */
    public function sent(): array
    {
        return $this->sent;
    }

    /**
     * Assert at least one request matched, or, with no callback, that anything was sent.
     *
     * @param (callable(RequestInterface): bool)|null $matches
     */
    public function assertSent(?callable $matches = null): void
    {
        foreach ($this->sent as $request) {
            if ($matches === null || $matches($request)) {
                self::pass();

                return;
            }
        }

        self::fail($matches === null
            ? 'Expected a request to be sent, but none was.'
            : \sprintf('No request matched. Sent: %s.', $this->describe()));
    }

    /**
     * Assert no request matched, or, with no callback, that nothing was sent at all.
     *
     * @param (callable(RequestInterface): bool)|null $matches
     */
    public function assertNotSent(?callable $matches = null): void
    {
        foreach ($this->sent as $request) {
            if ($matches === null || $matches($request)) {
                self::fail(\sprintf('Expected no matching request, but %s %s was sent.', $request->getMethod(), (string) $request->getUri()));
            }
        }

        self::pass();
    }

    public function assertNothingSent(): void
    {
        $this->assertNotSent();
    }

    public function assertSentCount(int $count): void
    {
        if (\count($this->sent) !== $count) {
            self::fail(\sprintf('Expected %d request%s, but %d %s sent: %s.', $count, $count === 1 ? '' : 's', \count($this->sent), \count($this->sent) === 1 ? 'was' : 'were', $this->describe()));
        }

        self::pass();
    }

    private function describe(): string
    {
        if ($this->sent === []) {
            return 'nothing';
        }

        return \implode(', ', \array_map(
            static fn(RequestInterface $request): string => $request->getMethod() . ' ' . (string) $request->getUri(),
            $this->sent,
        ));
    }

    /**
     * Count a passing assertion when PHPUnit is running, so a test asserting only through the fake
     * is not reported as risky.
     */
    private static function pass(): void
    {
        if (\class_exists(\PHPUnit\Framework\Assert::class)) {
            \PHPUnit\Framework\Assert::assertThat(true, new \PHPUnit\Framework\Constraint\IsTrue());
        }
    }

    /**
     * Fail the test: through PHPUnit when it is running, otherwise as an assertion error.
     */
    private static function fail(string $message): never
    {
        if (\class_exists(\PHPUnit\Framework\Assert::class)) {
            \PHPUnit\Framework\Assert::fail($message);
        }

        throw new \AssertionError($message);
    }
}
