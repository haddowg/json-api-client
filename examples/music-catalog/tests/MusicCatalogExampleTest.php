<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Examples\MusicCatalog\Tests;

use haddowg\JsonApiClient\Atomic\Tx;
use haddowg\JsonApiClient\ClientOptions;
use haddowg\JsonApiClient\Examples\MusicCatalog\Album;
use haddowg\JsonApiClient\Examples\MusicCatalog\AlbumCreate;
use haddowg\JsonApiClient\Examples\MusicCatalog\AlbumStatus;
use haddowg\JsonApiClient\Examples\MusicCatalog\AlbumTracksRelatedQuery;
use haddowg\JsonApiClient\Examples\MusicCatalog\Artist;
use haddowg\JsonApiClient\Examples\MusicCatalog\MusicCatalogClient;
use haddowg\JsonApiClient\Examples\MusicCatalog\Support\ExampleServer;
use haddowg\JsonApiClient\Examples\MusicCatalog\Track;
use haddowg\JsonApiClient\Exceptions\Codes\ResourceNotFoundError;
use haddowg\JsonApiClient\Exceptions\NotFound;
use haddowg\JsonApiClient\Exceptions\RelationNotIncludedException;
use haddowg\JsonApiClient\Exceptions\SchemaViolation;
use haddowg\JsonApiClient\Exceptions\UnknownFilterException;
use haddowg\JsonApiClient\Exceptions\ValidationFailed;
use haddowg\JsonApiClient\Pagination\PageNumber;
use haddowg\JsonApiClient\Testing\FakeTransport;
use haddowg\JsonApiClient\Validation\OpenApiValidator;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * The worked example, and the usage reference the documentation's snippets are taken from.
 *
 * `../generated` is exactly what `json-api-client-codegen` writes from the music catalog's
 * OpenAPI document (`../openapi.json`), and {@see ExampleServer} answers from that same document's
 * examples. So every snippet here is a real call against generated code, checked by PHPStan at
 * level 9 and run on every build, and a snippet that stops type-checking or behaving fails CI.
 */
final class MusicCatalogExampleTest extends TestCase
{
    private ExampleServer $server;

    private MusicCatalogClient $client;

    protected function setUp(): void
    {
        $psr17 = new Psr17Factory();
        $this->server = ExampleServer::fromFile(__DIR__ . '/../openapi.json', $psr17, $psr17);

        $this->client = new MusicCatalogClient(new ClientOptions(
            baseUrl: 'https://music.example',
            transport: $this->server,
            headers: fn(): array => ['Authorization' => 'Bearer ' . $this->token()],
            requestFactory: $psr17,
            streamFactory: $psr17,
        ));
    }

    public function testReadACollectionWithAnIncludeAndASparseFieldset(): void
    {
        $client = $this->client;

        $albums = $client->albums->query()
            ->withArtist()
            ->fields(['albums' => ['title', 'status', 'artist']])
            ->get();

        $album = $albums[0];
        self::assertSame('Abbey Road', $album->title());
        self::assertSame(AlbumStatus::Released, $album->status());
        self::assertSame('The Beatles', $album->artist()?->name());

        self::assertSame('include=artist&fields[albums]=title,status,artist', \rawurldecode($this->lastRequest()->getUri()->getQuery()));
    }

    public function testReadOneAndItsRelations(): void
    {
        $client = $this->client;

        $album = $client->albums->id('1')->get();
        self::assertSame('Abbey Road', $album->title());
        self::assertSame('1', $album->artistRef()?->id);

        $artist = $client->albums->id('1')->artist()->related();
        self::assertSame('The Beatles', $artist?->name());

        // `artist()` is a static error until `withArtist()`. Narrowed by hand, the runtime
        // still refuses rather than answering with a null that would read as "no artist".
        self::assertInstanceOf(Album::class, $album);
        $this->expectException(RelationNotIncludedException::class);
        $album->artist();
    }

    public function testFilterSortAndPage(): void
    {
        $client = $this->client;

        $albums = $client->albums->query()
            ->whereTitle('Abbey Road')
            ->whereRating(['min' => 4.0])
            ->sort('-releasedAt', 'title')
            ->page(1, size: 15)
            ->get();

        self::assertSame(
            'filter[title]=Abbey Road&filter[rating][min]=4&sort=-releasedAt,title&page[number]=1&page[size]=15',
            \rawurldecode($this->lastRequest()->getUri()->getQuery()),
        );
        self::assertInstanceOf(PageNumber::class, $albums->_page());

        $this->expectException(UnknownFilterException::class);
        $client->albums->query()->filterRaw(['titel' => 'Abbey Road']);
    }

    public function testShapeAnIncludedRelationWithARelationshipQuery(): void
    {
        $client = $this->client;

        $client->albums->query()
            ->withTracks(fn(AlbumTracksRelatedQuery $t) => $t->whereExplicit(false)->sort('trackNumber'))
            ->get();

        self::assertSame(
            'include=tracks&relatedQuery[tracks][filter][explicit]=false&relatedQuery[tracks][sort]=trackNumber',
            \rawurldecode($this->lastRequest()->getUri()->getQuery()),
        );
        self::assertStringContainsString(Album::RELATIONSHIP_QUERIES_PROFILE, $this->lastRequest()->getHeaderLine('Accept'));

        $tracks = $client->albums->id('1')->tracks()->related(fn(AlbumTracksRelatedQuery $t) => $t->sort('-title'));
        self::assertSame('sort=-title', \rawurldecode($this->lastRequest()->getUri()->getQuery()));
        self::assertCount(1, $tracks);
    }

    public function testCreateInEachOfTheThreeForms(): void
    {
        $client = $this->client;

        // The named-argument DTO: the form PHPStan checks completely.
        $album = $client->albums->create(new AlbumCreate(
            title: 'Abbey Road',
            status: AlbumStatus::Released,
            artist: Artist::ref('1'),
        ));
        self::assertSame('1', $album->id());

        // The fluent builder, for assembly that is conditional.
        $client->albums->create(
            Album::create()
                ->title('Abbey Road')
                ->when(true, fn($b) => $b->status(AlbumStatus::Released)),
        );

        // The array, for input assembled at run time.
        $client->albums->create(['title' => 'Abbey Road', 'status' => 'released']);

        self::assertSame(
            ['data' => ['type' => 'albums', 'attributes' => ['title' => 'Abbey Road', 'status' => 'released']]],
            \json_decode((string) $this->lastRequest()->getBody(), true),
        );
    }

    public function testUpdateDeleteAndWriteARelationship(): void
    {
        $client = $this->client;

        $album = $client->albums->id('1')->update(Album::update()->title('Abbey Road (Remastered)'));
        self::assertSame('PATCH', $this->lastRequest()->getMethod());
        self::assertSame('Abbey Road', $album->title(), 'the example server answers with its example');

        $client->albums->id('1')->tracks()->add([Track::ref('7')]);
        self::assertSame('/albums/1/relationships/tracks', $this->lastRequest()->getUri()->getPath());

        // A resource just read reaches the same relation through its handle.
        $client->albums->id('1')->get()->_handle()->artist()->set(Artist::ref('2'));
        self::assertSame('{"data":{"type":"artists","id":"2"}}', (string) $this->lastRequest()->getBody());

        $client->albums->id('1')->delete();
        self::assertSame('DELETE', $this->lastRequest()->getMethod());
    }

    public function testCallACustomAction(): void
    {
        $client = $this->client;

        $csv = $client->albums->actions()->export(accept: 'text/csv');
        self::assertIsString($csv);
        self::assertStringStartsWith('id,', $csv);

        $summary = $client->albums->actions()->summary();
        self::assertSame(['published' => 12, 'unpublished' => 3, 'total' => 15], $summary['albums']);
    }

    public function testRunAnAtomicBatch(): void
    {
        $client = $this->client;

        [$artist, $album] = $client->atomic(function (Tx $tx): array {
            $artist = $tx->create(Artist::create()->name('Boards of Canada'));
            $album = $tx->create(Album::create()->title('Geogaddi')->artist($artist));

            return [$artist, $album];
        });

        self::assertSame('100', $artist->id());
        self::assertSame('Geogaddi', $album->resource()->title());
        self::assertStringContainsString(
            '"relationships":{"artist":{"data":{"type":"artists","lid":"atomic-0"}}}',
            (string) $this->lastRequest()->getBody(),
        );
    }

    public function testCatchAnErrorByItsStatusOrItsCode(): void
    {
        $psr17 = new Psr17Factory();
        $fake = (new FakeTransport($psr17, $psr17))
            ->respondWith(404, ['errors' => [['status' => '404', 'code' => 'RESOURCE_NOT_FOUND', 'title' => 'Not Found']]])
            ->respondWith(422, ['errors' => [
                ['status' => '422', 'title' => 'Invalid', 'detail' => 'The title must not be empty.', 'source' => ['pointer' => '/data/attributes/title']],
            ]]);
        $client = new MusicCatalogClient(new ClientOptions('https://music.example', $fake, requestFactory: $psr17, streamFactory: $psr17));

        try {
            $client->albums->id('999')->get();
            self::fail('Expected the album not to be found.');
        } catch (ResourceNotFoundError $e) {
            // The code's class extends its status class, so `catch (NotFound $e)` catches it too.
            self::assertInstanceOf(NotFound::class, $e);
            self::assertSame(404, $e->statusCode());
        }

        try {
            $client->albums->create(['title' => 'x']);
            self::fail('Expected a validation failure.');
        } catch (ValidationFailed $e) {
            self::assertSame(['title'], \array_keys($e->byPath()));
        }

        $fake->assertSent(fn(RequestInterface $r) => $r->getMethod() === 'POST');
    }

    public function testValidateResponsesAgainstTheDocument(): void
    {
        $psr17 = new Psr17Factory();
        $fake = (new FakeTransport($psr17, $psr17))->respondWith(200, ['data' => ['type' => 'albums', 'id' => '1', 'attributes' => ['title' => 42]]]);

        $client = new MusicCatalogClient((new ClientOptions('https://music.example', $fake, requestFactory: $psr17, streamFactory: $psr17))
            ->withValidator(OpenApiValidator::fromFile(__DIR__ . '/../openapi.json')));

        $this->expectException(SchemaViolation::class);
        $client->albums->id('1')->get();
    }

    private function token(): string
    {
        return 'example-token';
    }

    private function lastRequest(): RequestInterface
    {
        return $this->server->requests[\count($this->server->requests) - 1] ?? throw new \LogicException('Nothing was sent.');
    }
}
