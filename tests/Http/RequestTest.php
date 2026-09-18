<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Http;

use haddowg\JsonApiClient\Http\Request;
use haddowg\JsonApiClient\MediaType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    public function testTheVerbHelpersNameTheirMethod(): void
    {
        self::assertSame('GET', Request::get('/albums')->method);
        self::assertSame('POST', Request::post('/albums')->method);
        self::assertSame('PATCH', Request::patch('/albums/1')->method);
        self::assertSame('DELETE', Request::delete('/albums/1')->method);
    }

    public function testABodyIsOptionalOnEveryVerb(): void
    {
        self::assertFalse(Request::post('/albums')->hasBody());
        self::assertTrue(Request::post('/albums', '{}')->hasBody());
        self::assertTrue(Request::delete('/albums/1/relationships/tracks', '{"data":[]}')->hasBody());
    }

    public function testItIsImmutable(): void
    {
        $original = Request::get('/albums');

        $withExt = $original->withExt([MediaType::ATOMIC_OPERATIONS]);
        $withProfiles = $original->withProfiles(['https://example.com/p']);
        $withHeader = $original->withHeader('X-Trace', 'abc');

        self::assertSame([], $original->ext);
        self::assertSame([], $original->profiles);
        self::assertSame([], $original->headers);
        self::assertSame([MediaType::ATOMIC_OPERATIONS], $withExt->ext);
        self::assertSame(['https://example.com/p'], $withProfiles->profiles);
        self::assertSame(['X-Trace' => 'abc'], $withHeader->headers);
    }

    public function testHeadersAccumulate(): void
    {
        $request = Request::get('/albums')
            ->withHeader('X-Trace', 'abc')
            ->withHeader('X-Tenant', 'acme')
            ->withHeader('X-Trace', 'def');

        self::assertSame(['X-Trace' => 'def', 'X-Tenant' => 'acme'], $request->headers);
    }

    public function testARawBodySetsItsOwnContentTypeAndLeavesAcceptAlone(): void
    {
        $request = Request::post('/albums/1/-actions/import')->withRawBody('a,b,c', 'text/csv');

        self::assertSame('a,b,c', $request->body);
        self::assertSame('text/csv', $request->contentType);
        self::assertNull($request->accept);
    }

    public function testNegotiationOptionsSurviveAlongsideARawBody(): void
    {
        $request = Request::post('/atomic')
            ->withExt([MediaType::ATOMIC_OPERATIONS])
            ->withHeader('X-Trace', 'abc')
            ->withProfiles(['https://example.com/p']);

        self::assertSame([MediaType::ATOMIC_OPERATIONS], $request->ext);
        self::assertSame(['https://example.com/p'], $request->profiles);
        self::assertSame(['X-Trace' => 'abc'], $request->headers);
    }
}
