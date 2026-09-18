<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Http;

use haddowg\JsonApiClient\Http\Response;
use Nyholm\Psr7\Response as PsrResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    public function testItReadsThePsrBodyOnceAndKeepsIt(): void
    {
        $response = Response::fromPsr(new PsrResponse(200, [], '{"data":[]}'));

        self::assertSame('{"data":[]}', $response->body);
        self::assertSame('{"data":[]}', $response->body);
    }

    public function testHeadersAreMatchedWithoutRegardToCase(): void
    {
        $response = Response::fromPsr(
            new PsrResponse(200, ['Content-Type' => 'application/vnd.api+json', 'X-Trace' => 'abc']),
        );

        self::assertSame('application/vnd.api+json', $response->header('content-type'));
        self::assertSame('application/vnd.api+json', $response->header('CONTENT-TYPE'));
        self::assertSame('abc', $response->header('X-Trace'));
        self::assertNull($response->header('X-Missing'));
    }

    public function testEveryHeaderValueIsKept(): void
    {
        $response = new Response(200, '', ['Link' => ['<a>; rel="next"', '<b>; rel="last"']]);

        self::assertSame(['Link' => ['<a>; rel="next"', '<b>; rel="last"']], $response->headers());
        self::assertSame('<a>; rel="next"', $response->header('Link'));
    }

    #[DataProvider('statuses')]
    public function testSuccessIsTheTwoHundredRange(int $status, bool $successful): void
    {
        self::assertSame($successful, (new Response($status, ''))->isSuccessful());
    }

    /**
     * @return iterable<string, array{int, bool}>
     */
    public static function statuses(): iterable
    {
        yield '200' => [200, true];
        yield '201' => [201, true];
        yield '204' => [204, true];
        yield '299' => [299, true];
        yield '304' => [304, false];
        yield '400' => [400, false];
        yield '500' => [500, false];
    }

    public function testAnEmptyBodyIsRecognisedWhicheverWayTheServerSaidIt(): void
    {
        self::assertFalse((new Response(204, ''))->hasBody());
        self::assertFalse((new Response(204, '{"meta":{}}'))->hasBody());
        self::assertFalse((new Response(200, '   '))->hasBody());
        self::assertTrue((new Response(200, '{"data":[]}'))->hasBody());
    }

    public function testTheMediaTypeDropsItsParameters(): void
    {
        $negotiated = new Response(200, '', [
            'Content-Type' => ['application/vnd.api+json; ext="https://jsonapi.org/ext/atomic"'],
        ]);

        self::assertSame('application/vnd.api+json', $negotiated->mediaType());
        self::assertSame('application/vnd.api+json', (new Response(200, '', ['Content-Type' => ['application/vnd.api+json']]))->mediaType());
        self::assertSame('text/csv', (new Response(200, '', ['Content-Type' => ['TEXT/CSV']]))->mediaType());
        self::assertNull((new Response(204, ''))->mediaType());
    }
}
