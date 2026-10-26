<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Resources;

use haddowg\JsonApiClient\ClientOptions;
use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Errors\ErrorDocument;
use haddowg\JsonApiClient\Errors\ErrorSource;
use haddowg\JsonApiClient\Exceptions\JsonApiErrorResponse;
use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Exceptions\NotAcceptable;
use haddowg\JsonApiClient\Http\Request;
use haddowg\JsonApiClient\Http\Response;
use haddowg\JsonApiClient\Http\Transport;
use haddowg\JsonApiClient\MediaType;
use haddowg\JsonApiClient\Resources\Fetcher;
use haddowg\JsonApiClient\Support\Wire;
use haddowg\JsonApiClient\Tests\Fixtures\RecordingHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Fetcher::class)]
#[CoversClass(MalformedDocument::class)]
#[UsesClass(ClientOptions::class)]
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
