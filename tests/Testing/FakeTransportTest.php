<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Testing;

use haddowg\JsonApiClient\Testing\FakeTransport;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

#[CoversClass(FakeTransport::class)]
final class FakeTransportTest extends TestCase
{
    public function testItAnswersFromTheQueueAndRecordsWhatWasSent(): void
    {
        $fake = self::fake()->respondWith(200, ['data' => null])->respondWith(204);

        $first = $fake->sendRequest(self::request('GET', '/albums/1'));
        $second = $fake->sendRequest(self::request('DELETE', '/albums/1'));

        self::assertSame('{"data":null}', (string) $first->getBody());
        self::assertSame('application/vnd.api+json', $first->getHeaderLine('Content-Type'));
        self::assertSame(204, $second->getStatusCode());
        self::assertCount(2, $fake->sent());

        $fake->assertSent(static fn(RequestInterface $r): bool => $r->getMethod() === 'DELETE');
        $fake->assertNotSent(static fn(RequestInterface $r): bool => $r->getMethod() === 'PATCH');
        $fake->assertSentCount(2);
    }

    public function testARequestWithNothingQueuedFails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('no response queued for GET https://api.example.com/albums');

        self::fake()->sendRequest(self::request('GET', '/albums'));
    }

    public function testAssertSentFailsWhenNothingMatches(): void
    {
        $fake = self::fake()->respondWith(200, '{}');
        $fake->sendRequest(self::request('GET', '/albums'));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('No request matched. Sent: GET https://api.example.com/albums.');

        $fake->assertSent(static fn(RequestInterface $r): bool => $r->getMethod() === 'POST');
    }

    public function testAssertNothingSentFailsOnceSomethingWasSent(): void
    {
        $fake = self::fake();
        $fake->assertNothingSent();

        $fake->respondWith(200, '{}')->sendRequest(self::request('GET', '/albums'));

        $this->expectException(AssertionFailedError::class);
        $fake->assertNothingSent();
    }

    public function testAssertSentCountNamesWhatWasSent(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Expected 1 request, but 0 were sent: nothing.');

        self::fake()->assertSentCount(1);
    }

    private static function fake(): FakeTransport
    {
        return new FakeTransport(new Psr17Factory(), new Psr17Factory());
    }

    private static function request(string $method, string $path): RequestInterface
    {
        return (new Psr17Factory())->createRequest($method, 'https://api.example.com' . $path);
    }
}
