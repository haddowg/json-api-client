<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests;

use haddowg\JsonApiClient\Document;
use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\JsonApi;
use haddowg\JsonApiClient\MediaType;
use haddowg\JsonApiClient\Support\Wire;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Document::class)]
#[CoversClass(JsonApi::class)]
#[UsesClass(MalformedDocument::class)]
#[UsesClass(MediaType::class)]
#[UsesClass(Wire::class)]
final class DocumentTest extends TestCase
{
    private const string BODY = <<<'JSON'
        {
          "jsonapi": {
            "version": "1.1",
            "ext": ["https://jsonapi.org/ext/atomic"],
            "profile": ["https://example.com/profiles/countable"],
            "meta": {"implementation": "haddowg/json-api"}
          },
          "meta": {"page": {"total": 3, "lastPage": 1}},
          "links": {"self": "/albums", "next": {"href": "/albums?page[number]=2"}},
          "data": [{"type": "albums", "id": "1"}]
        }
        JSON;

    public function testItReadsTheTopLevelMembersAndLeavesTheDataAlone(): void
    {
        $document = Document::fromJson(self::BODY);

        self::assertSame('1.1', $document->jsonapi()->version);
        self::assertSame(['page' => ['total' => 3, 'lastPage' => 1]], $document->meta());
        self::assertSame('/albums', $document->self());
        self::assertSame(['total' => 3, 'lastPage' => 1], $document->pageMeta());
    }

    public function testALinkResolvesThroughBothFormsTheSpecAllows(): void
    {
        $document = Document::fromJson(self::BODY);

        self::assertSame('/albums', $document->link('self'));
        self::assertSame('/albums?page[number]=2', $document->link('next'));
        self::assertNull($document->link('prev'));
    }

    public function testExtAndProfileAreTypedBecauseTheyAreWhatVerifiesNegotiation(): void
    {
        $jsonapi = Document::fromJson(self::BODY)->jsonapi();

        self::assertSame(['https://jsonapi.org/ext/atomic'], $jsonapi->ext);
        self::assertSame(['https://example.com/profiles/countable'], $jsonapi->profile);
        self::assertTrue($jsonapi->hasExt(MediaType::ATOMIC_OPERATIONS));
        self::assertTrue($jsonapi->hasAtomicOperations());
        self::assertTrue($jsonapi->hasProfile('https://example.com/profiles/countable'));
        self::assertFalse($jsonapi->hasProfile('https://example.com/profiles/relationship-queries'));
        self::assertSame(['implementation' => 'haddowg/json-api'], $jsonapi->meta());
    }

    public function testJsonapiIsNeverNullSoTheChainIsAlwaysSafeToWrite(): void
    {
        $document = Document::fromJson('{"data":[]}');

        self::assertNull($document->jsonapi()->version);
        self::assertSame([], $document->jsonapi()->ext);
        self::assertSame([], $document->jsonapi()->profile);
        self::assertFalse($document->jsonapi()->hasAtomicOperations());
    }

    public function testAnAbsentVersionStaysNullRatherThanBeingInvented(): void
    {
        // The spec's own default is 1.0, but a client asserting on the version needs to know
        // whether the server actually said so.
        self::assertNull(JsonApi::absent()->version);
    }

    public function testMembersOfTheWrongTypeAreDroppedRatherThanThrownOn(): void
    {
        $jsonapi = JsonApi::fromArray(['version' => 1.1, 'ext' => 'atomic', 'profile' => ['ok', 7]]);

        self::assertNull($jsonapi->version);
        self::assertSame([], $jsonapi->ext);
        self::assertSame(['ok'], $jsonapi->profile);
    }

    public function testABodyThatIsNotAJsonObjectIsRejected(): void
    {
        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('The response body is not a JSON:API document: expected a JSON object.');

        Document::fromJson('<html>502 Bad Gateway</html>');
    }

    public function testADocumentCanBeBuiltDirectlyForATestOrAFake(): void
    {
        $document = Document::of(new JsonApi('1.1'), ['a' => 1], ['self' => '/albums']);

        self::assertSame('1.1', $document->jsonapi()->version);
        self::assertSame(['a' => 1], $document->meta());
        self::assertSame(['self' => '/albums'], $document->links());
    }

    public function testPageMetaIsEmptyWhenTheDocumentCarriesNone(): void
    {
        self::assertSame([], Document::fromJson('{"data":[],"meta":{"other":1}}')->pageMeta());
    }
}
