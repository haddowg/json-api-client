<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Http;

use haddowg\JsonApiClient\ClientOptions;
use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Errors\ErrorDocument;
use haddowg\JsonApiClient\Errors\ErrorSource;
use haddowg\JsonApiClient\Exceptions\JsonApiErrorResponse;
use haddowg\JsonApiClient\Exceptions\NotFound;
use haddowg\JsonApiClient\Exceptions\ServerError;
use haddowg\JsonApiClient\Exceptions\TransportException;
use haddowg\JsonApiClient\Exceptions\ValidationFailed;
use haddowg\JsonApiClient\Http\Discovery;
use haddowg\JsonApiClient\Http\Request;
use haddowg\JsonApiClient\Http\Response;
use haddowg\JsonApiClient\Http\Transport;
use haddowg\JsonApiClient\MediaType;
use haddowg\JsonApiClient\Support\Wire;
use haddowg\JsonApiClient\Tests\Fixtures\NetworkFailure;
use haddowg\JsonApiClient\Tests\Fixtures\RecordingHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as PsrResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Transport::class)]
#[CoversClass(ClientOptions::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
#[UsesClass(MediaType::class)]
#[UsesClass(Discovery::class)]
#[UsesClass(Error::class)]
#[UsesClass(ErrorDocument::class)]
#[UsesClass(ErrorSource::class)]
#[UsesClass(JsonApiErrorResponse::class)]
#[UsesClass(NotFound::class)]
#[UsesClass(ServerError::class)]
#[UsesClass(TransportException::class)]
#[UsesClass(ValidationFailed::class)]
#[UsesClass(Wire::class)]
final class TransportTest extends TestCase
{
    public function testItNegotiatesTheJsonApiMediaTypeOnEveryRequest(): void
    {
        $client = RecordingHttpClient::answering(200, '{"data":[]}');

        $this->transport($client)->send(Request::get('/albums'));

        self::assertSame('application/vnd.api+json', $client->lastRequest()->getHeaderLine('Accept'));
        self::assertSame('', $client->lastRequest()->getHeaderLine('Content-Type'));
    }

    public function testExtensionsAndProfilesBecomeMediaTypeParameters(): void
    {
        $client = RecordingHttpClient::answering(200, '{"data":[]}');

        $this->transport($client)->send(
            Request::get('/albums')
                ->withExt([MediaType::ATOMIC_OPERATIONS])
                ->withProfiles(['https://example.com/profiles/countable']),
        );

        self::assertSame(
            'application/vnd.api+json; ext="https://jsonapi.org/ext/atomic"; profile="https://example.com/profiles/countable"',
            $client->lastRequest()->getHeaderLine('Accept'),
        );
    }

    public function testABodyIsSentUnderTheSameNegotiatedMediaType(): void
    {
        $client = RecordingHttpClient::answering(201, '{"data":{"type":"albums","id":"1"}}');

        $this->transport($client)->send(
            Request::post('/albums', '{"data":{"type":"albums"}}')->withExt([MediaType::ATOMIC_OPERATIONS]),
        );

        $sent = $client->lastRequest();

        self::assertSame(
            'application/vnd.api+json; ext="https://jsonapi.org/ext/atomic"',
            $sent->getHeaderLine('Content-Type'),
        );
        self::assertSame($sent->getHeaderLine('Accept'), $sent->getHeaderLine('Content-Type'));
        self::assertSame('{"data":{"type":"albums"}}', (string) $sent->getBody());
    }

    public function testARawBodyOverridesContentTypeWithoutTouchingAccept(): void
    {
        $client = RecordingHttpClient::answering(202);

        $this->transport($client)->send(
            Request::post('/albums/1/-actions/import')->withRawBody('a,b,c', 'text/csv'),
        );

        self::assertSame('text/csv', $client->lastRequest()->getHeaderLine('Content-Type'));
        self::assertSame('application/vnd.api+json', $client->lastRequest()->getHeaderLine('Accept'));
    }

    public function testTheHeaderProviderIsResolvedOnEveryRequestSoARefreshedTokenIsPickedUp(): void
    {
        // The whole point of a provider rather than a header map: no rebuilding the client.
        $client = RecordingHttpClient::answering(200, '{"data":[]}');
        $tokens = new class {
            public string $current = 'first';
        };

        $transport = new Transport(new ClientOptions(
            baseUrl: 'https://api.example.com',
            transport: $client,
            headers: static fn(): array => ['Authorization' => 'Bearer ' . $tokens->current],
            requestFactory: new Psr17Factory(),
            streamFactory: new Psr17Factory(),
        ));

        $transport->send(Request::get('/albums'));
        $tokens->current = 'refreshed';
        $transport->send(Request::get('/albums'));

        self::assertSame('Bearer first', $client->requests[0]->getHeaderLine('Authorization'));
        self::assertSame('Bearer refreshed', $client->requests[1]->getHeaderLine('Authorization'));
    }

    public function testACallSiteHeaderWinsOverTheProviderAndOverTheNegotiatedDefault(): void
    {
        $client = RecordingHttpClient::answering(200, '{"data":[]}');

        $transport = new Transport(new ClientOptions(
            baseUrl: 'https://api.example.com',
            transport: $client,
            headers: static fn(): array => ['X-Trace' => 'from-provider', 'Accept-Language' => 'en'],
            requestFactory: new Psr17Factory(),
            streamFactory: new Psr17Factory(),
        ));

        $transport->send(Request::get('/albums')->withHeader('X-Trace', 'from-call-site'));

        self::assertSame('from-call-site', $client->lastRequest()->getHeaderLine('X-Trace'));
        self::assertSame('en', $client->lastRequest()->getHeaderLine('Accept-Language'));
    }

    public function testARelativePathIsResolvedAgainstTheBaseUrl(): void
    {
        $client = RecordingHttpClient::answering(200, '{"data":[]}');
        $transport = $this->transport($client, 'https://api.example.com/v1/');

        $transport->send(Request::get('/albums'));
        $transport->send(Request::get('albums/1'));
        $transport->send(Request::get(''));

        self::assertSame('https://api.example.com/v1/albums', (string) $client->requests[0]->getUri());
        self::assertSame('https://api.example.com/v1/albums/1', (string) $client->requests[1]->getUri());
        self::assertSame('https://api.example.com/v1', (string) $client->requests[2]->getUri());
    }

    public function testAnAbsoluteUrlIsSentVerbatimBecauseAPageLinkIsAlreadyAddressed(): void
    {
        $client = RecordingHttpClient::answering(200, '{"data":[]}');

        $this->transport($client)->send(Request::get('https://other.example.com/albums?page%5Bnumber%5D=2'));

        self::assertSame(
            'https://other.example.com/albums?page%5Bnumber%5D=2',
            (string) $client->lastRequest()->getUri(),
        );
    }

    public function testASuccessfulResponseComesBackWithItsBodyAlreadyRead(): void
    {
        $client = RecordingHttpClient::answering(200, '{"data":[]}');

        $response = $this->transport($client)->send(Request::get('/albums'));

        self::assertSame(200, $response->status);
        self::assertSame('{"data":[]}', $response->body);
        self::assertSame('{"data":[]}', $response->body, 'a stream can be read once; a string cannot run out');
        self::assertTrue($response->isSuccessful());
    }

    public function testANonTwoHundredBecomesTheExceptionItsStatusMapsTo(): void
    {
        $client = new RecordingHttpClient(new PsrResponse(404, [], (string) \json_encode([
            'errors' => [['status' => '404', 'title' => 'Resource Not Found']],
        ])));

        try {
            $this->transport($client)->send(Request::get('/albums/999'));
            self::fail('Expected a NotFound exception.');
        } catch (NotFound $e) {
            self::assertSame(404, $e->statusCode());
            self::assertCount(1, $e->errors());
            self::assertSame('Resource Not Found', $e->errors()[0]->title);
        }
    }

    public function testAValidationFailureArrivesGroupedByInputPath(): void
    {
        $client = new RecordingHttpClient(new PsrResponse(422, [], (string) \json_encode([
            'errors' => [['source' => ['pointer' => '/data/attributes/title']]],
        ])));

        try {
            $this->transport($client)->send(Request::post('/albums', '{}'));
            self::fail('Expected a ValidationFailed exception.');
        } catch (ValidationFailed $e) {
            self::assertSame(['title'], \array_keys($e->byPath()));
        }
    }

    public function testAGatewayThatAnswersWithHtmlStillProducesTheRightException(): void
    {
        $client = new RecordingHttpClient(new PsrResponse(502, [], '<html>Bad Gateway</html>'));

        $this->expectException(ServerError::class);

        $this->transport($client)->send(Request::get('/albums'));
    }

    public function testARequestThatNeverReachedTheServerIsWrappedWithTheOriginalAttached(): void
    {
        $failure = new NetworkFailure('Connection refused');
        $client = new RecordingHttpClient($failure);

        try {
            $this->transport($client)->send(Request::get('/albums'));
            self::fail('Expected a TransportException.');
        } catch (TransportException $e) {
            self::assertSame($failure, $e->getPrevious());
            self::assertSame('GET', $e->request()?->getMethod());
            self::assertStringContainsString('Connection refused', $e->getMessage());
            self::assertStringContainsString('https://api.example.com/albums', $e->getMessage());
        }
    }

    public function testAPsr18ClientIsDiscoveredWhenNoneIsGiven(): void
    {
        $options = new ClientOptions(
            baseUrl: 'https://api.example.com',
            requestFactory: new Psr17Factory(),
            streamFactory: new Psr17Factory(),
        );

        Discovery::forget();

        // php-http/discovery is installed here, so a client is found rather than refused —
        // which is exactly the convenience the optional dependency buys. It is found once and
        // reused, not rediscovered per request.
        self::assertSame($options->transport(), $options->transport());
    }

    public function testTheErrorForAMissingPsr18ClientNamesTheWayOut(): void
    {
        $exception = TransportException::noHttpClient();

        self::assertStringContainsString('ClientOptions $transport', $exception->getMessage());
        self::assertStringContainsString('php-http/discovery', $exception->getMessage());
        self::assertNull($exception->request());
    }

    public function testFactoriesAreDiscoveredWhenNoneAreGiven(): void
    {
        Discovery::forget();

        $client = RecordingHttpClient::answering(200, '{"data":[]}');
        $transport = new Transport(new ClientOptions(baseUrl: 'https://api.example.com', transport: $client));

        $response = $transport->send(Request::get('/albums'));

        self::assertSame(200, $response->status);
        self::assertSame('https://api.example.com/albums', (string) $client->lastRequest()->getUri());
    }

    public function testBuildComposesTheRequestWithoutSendingIt(): void
    {
        $client = RecordingHttpClient::answering();

        $request = $this->transport($client)->build(Request::patch('/albums/1', '{"data":{}}'));

        self::assertSame('PATCH', $request->getMethod());
        self::assertSame('https://api.example.com/albums/1', (string) $request->getUri());
        self::assertSame([], $client->requests);
    }

    private function transport(RecordingHttpClient $client, string $baseUrl = 'https://api.example.com'): Transport
    {
        return new Transport(new ClientOptions(
            baseUrl: $baseUrl,
            transport: $client,
            requestFactory: new Psr17Factory(),
            streamFactory: new Psr17Factory(),
        ));
    }
}
