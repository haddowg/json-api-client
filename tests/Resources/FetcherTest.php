<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Resources;

use haddowg\JsonApiClient\ActionLocation;
use haddowg\JsonApiClient\ClientOptions;
use haddowg\JsonApiClient\Document;
use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Errors\ErrorDocument;
use haddowg\JsonApiClient\Errors\ErrorSource;
use haddowg\JsonApiClient\Exceptions\JsonApiErrorResponse;
use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Exceptions\NotAcceptable;
use haddowg\JsonApiClient\Http\Request;
use haddowg\JsonApiClient\Http\Response;
use haddowg\JsonApiClient\Http\Transport;
use haddowg\JsonApiClient\JsonApi;
use haddowg\JsonApiClient\MediaType;
use haddowg\JsonApiClient\Query\ReadQuery;
use haddowg\JsonApiClient\Resources\Fetcher;
use haddowg\JsonApiClient\Resources\ResourceContext;
use haddowg\JsonApiClient\Support\Wire;
use haddowg\JsonApiClient\Tests\Fixtures\RecordingHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Fetcher::class)]
#[CoversClass(MalformedDocument::class)]
#[CoversClass(ActionLocation::class)]
#[UsesClass(ClientOptions::class)]
#[UsesClass(Document::class)]
#[UsesClass(JsonApi::class)]
#[UsesClass(ReadQuery::class)]
#[UsesClass(ResourceContext::class)]
#[UsesClass(Error::class)]
#[UsesClass(ErrorDocument::class)]
#[UsesClass(ErrorSource::class)]
#[UsesClass(JsonApiErrorResponse::class)]
#[UsesClass(NotAcceptable::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
#[UsesClass(Transport::class)]
#[UsesClass(MediaType::class)]
#[UsesClass(Wire::class)]
final class FetcherTest extends TestCase
{
    private const string LOCATION = '{"links":{"self":"https://api.example.com/articles/1/-actions/download","location":{"href":"https://storage.example.com/1.csv?signature=abc","type":"text/csv","meta":{"expiresAt":"2030-01-01T00:00:00+00:00"}}},"meta":{"expiresAt":"2030-01-01T00:00:00+00:00"},"jsonapi":{"version":"1.1"}}';

    public function testATextBodyComesBackAsItWasSent(): void
    {
        $client = RecordingHttpClient::answering(200, "id,title\n1,Hello\n", ['Content-Type' => 'text/csv']);

        $csv = $this->fetcher($client)->text(Request::get('/articles/-actions/report')->withAccept('text/csv'));

        self::assertSame("id,title\n1,Hello\n", $csv);
        self::assertSame('text/csv', $client->lastRequest()->getHeaderLine('Accept'));
    }

    public function testABinaryBodyComesBackAsAStream(): void
    {
        $png = "\x89PNG\r\n\x1a\n";
        $client = RecordingHttpClient::answering(200, $png, ['Content-Type' => 'image/png']);

        $stream = $this->fetcher($client)->stream(Request::get('/articles/1/-actions/cover')->withAccept('image/png'));

        self::assertSame($png, (string) $stream);
    }

    public function testAJsonBodyComesBackDecoded(): void
    {
        $client = RecordingHttpClient::answering(200, '{"total":3,"byStatus":{"draft":1}}', ['Content-Type' => 'application/json']);

        $summary = $this->fetcher($client)->json(Request::get('/articles/-actions/report')->withAccept('application/json'));

        self::assertSame(['total' => 3, 'byStatus' => ['draft' => 1]], $summary);
    }

    public function testAJsonObjectComesBackWithStringKeys(): void
    {
        $client = RecordingHttpClient::answering(200, '{"total":3}', ['Content-Type' => 'application/json']);

        self::assertSame(['total' => 3], $this->fetcher($client)->jsonObject(Request::get('/articles/-actions/report')));
    }

    public function testAJsonBodyThatIsNotAnObjectIsMalformedWhereAnObjectIsExpected(): void
    {
        $client = RecordingHttpClient::answering(200, '[1,2]', ['Content-Type' => 'application/json']);

        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('The response body is not a JSON object.');

        $this->fetcher($client)->jsonObject(Request::get('/articles/-actions/report'));
    }

    public function testAJsonBodyThatIsNotJsonIsMalformed(): void
    {
        $client = RecordingHttpClient::answering(200, '<html>', ['Content-Type' => 'application/json']);

        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('The response body is not valid JSON');

        $this->fetcher($client)->json(Request::get('/articles/-actions/report')->withAccept('application/json'));
    }

    public function testARefusedMediaTypeIsTheJsonApiErrorTheServerSent(): void
    {
        $client = RecordingHttpClient::answering(406, '{"errors":[{"status":"406","code":"MEDIA_TYPE_UNACCEPTABLE"}]}');

        $this->expectException(NotAcceptable::class);

        $this->fetcher($client)->text(Request::get('/articles/-actions/report')->withAccept('text/xml'));
    }

    public function testALocationDocumentIsReadIntoItsUrlTypeAndExpiry(): void
    {
        $client = RecordingHttpClient::answering(200, self::LOCATION);

        $location = $this->fetcher($client)->location(Request::get('/articles/1/-actions/download'));

        self::assertSame('https://storage.example.com/1.csv?signature=abc', $location->url);
        self::assertSame('text/csv', $location->type);
        self::assertEquals(new \DateTimeImmutable('2030-01-01T00:00:00+00:00'), $location->expiresAt);
        self::assertSame(MediaType::JSON_API, $client->lastRequest()->getHeaderLine('Accept'));
        self::assertCount(1, $client->requests);
    }

    public function testALocationThatDoesNotExpireHasNoExpiry(): void
    {
        $client = RecordingHttpClient::answering(200, '{"links":{"location":{"href":"/files/1.bin","type":"application/octet-stream"}},"meta":{"expiresAt":null}}');

        $location = $this->fetcher($client)->location(Request::get('/files/1/-actions/download'));

        self::assertSame('/files/1.bin', $location->url);
        self::assertNull($location->expiresAt);
    }

    public function testTheDocumentExpiryStandsInForTheLinks(): void
    {
        $client = RecordingHttpClient::answering(200, '{"links":{"location":{"href":"/1.csv","type":"text/csv"}},"meta":{"expiresAt":"2030-01-01T00:00:00Z"}}');

        $location = $this->fetcher($client)->location(Request::get('/files/1/-actions/download'));

        self::assertEquals(new \DateTimeImmutable('2030-01-01T00:00:00Z'), $location->expiresAt);
    }

    public function testADocumentWithoutALocationLinkIsMalformed(): void
    {
        $client = RecordingHttpClient::answering(200, '{"meta":{"expiresAt":null}}');

        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('"location" link');

        $this->fetcher($client)->location(Request::get('/files/1/-actions/download'));
    }

    public function testALocationLinkWithoutATypeIsMalformed(): void
    {
        $client = RecordingHttpClient::answering(200, '{"links":{"location":{"href":"/1.csv"}},"meta":{"expiresAt":null}}');

        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('The location link is missing a string "type" member.');

        $this->fetcher($client)->location(Request::get('/files/1/-actions/download'));
    }

    public function testAnExpiryThatIsNotADateIsMalformed(): void
    {
        $client = RecordingHttpClient::answering(200, '{"links":{"location":{"href":"/1.csv","type":"text/csv","meta":{"expiresAt":"soon"}}},"meta":{"expiresAt":"soon"}}');

        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('"expiresAt"');

        $this->fetcher($client)->location(Request::get('/files/1/-actions/download'));
    }

    public function testABodyThatIsNotAnObjectIsMalformedWhereALocationIsExpected(): void
    {
        $client = RecordingHttpClient::answering(200, '[]');

        $this->expectException(MalformedDocument::class);

        $this->fetcher($client)->location(Request::get('/files/1/-actions/download'));
    }

    public function testReadOrLocationTellsALocationDocumentFromAResourceDocument(): void
    {
        $fetcher = $this->fetcher(RecordingHttpClient::answering(200, self::LOCATION));
        $location = $fetcher->readOrLocation(Request::get('/articles/1/-actions/revision'));

        self::assertInstanceOf(ActionLocation::class, $location);
        self::assertSame('text/csv', $location->type);

        $fetcher = $this->fetcher(RecordingHttpClient::answering(200, '{"data":{"type":"articles","id":"1","attributes":{"title":"Hello"}},"links":{"self":"/articles/1/-actions/revision"}}'));
        $context = $fetcher->readOrLocation(Request::get('/articles/1/-actions/revision'), new ReadQuery());

        self::assertInstanceOf(ResourceContext::class, $context);
        self::assertSame('1', $context->primary()['id'] ?? null);
    }

    public function testReadOrLocationRefusesABodyThatIsNotAnObject(): void
    {
        $this->expectException(MalformedDocument::class);

        $this->fetcher(RecordingHttpClient::answering(200, 'nope'))->readOrLocation(Request::get('/articles/1/-actions/revision'));
    }

    private function fetcher(RecordingHttpClient $client): Fetcher
    {
        return new Fetcher(new Transport(new ClientOptions(
            baseUrl: 'https://api.example.com',
            transport: $client,
            requestFactory: new Psr17Factory(),
            streamFactory: new Psr17Factory(),
        )));
    }
}
