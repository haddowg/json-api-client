<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated;

use haddowg\JsonApiClient\ClientOptions;
use haddowg\JsonApiClient\Exceptions\FieldNotSelectedException;
use haddowg\JsonApiClient\Exceptions\MissingRequiredMemberException;
use haddowg\JsonApiClient\Exceptions\RelationNotIncludedException;
use haddowg\JsonApiClient\Exceptions\UnknownAttributeException;
use haddowg\JsonApiClient\Exceptions\UnknownFieldException;
use haddowg\JsonApiClient\Exceptions\UnknownFilterException;
use haddowg\JsonApiClient\Exceptions\UnknownSortException;
use haddowg\JsonApiClient\Tests\Fixtures\RecordingHttpClient;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\Album;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumCreate;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumStatus;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\Artist;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\MusicCatalogClient;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\Track;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as PsrResponse;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

/**
 * The reference artefact used the way an application would use it.
 *
 * Every negative case here is an assertion rather than a comment: a relation that was not
 * included, an attribute a fieldset excluded, a filter name that does not exist, a sort token
 * that does not exist, a fieldset member that does not exist, and a builder missing a required
 * member. The cases that must fail at *compile* time instead live in
 * {@see MusicCatalogTypes}, which is analysed and never run.
 *
 * The whole class is gated on PHP 8.4. The artefact targets the 8.4 form — method accessors
 * plus delegating get-only property hooks — and hook syntax is a parse error on 8.3, which the
 * CI matrix still runs. The gate is what keeps the 8.3 leg from fatally erroring on autoload,
 * and it is the reason `tests/Generated/` is analysed by PHPStan (8.4 in CI) rather than merely
 * executed.
 */
#[RequiresPhp('>= 8.4.0')]
final class MusicCatalogUsageTest extends TestCase
{
    public function testIncludedRelationsComeBackAsResources(): void
    {
        $http = self::answering(self::collectionDocument());
        $client = self::client($http);

        $albums = $client->albums->query()->withArtist()->withTracks()->get();

        self::assertStringContainsString('include=artist%2Ctracks', (string) $http->lastRequest()->getUri());
        self::assertCount(2, $albums);

        $album = $albums[0];

        self::assertSame('Geogaddi', $album->title());
        self::assertSame('Boards of Canada', $album->artist()?->name());
        self::assertCount(2, $album->tracks());

        // The 8.4 property hooks are one-line delegations to the same methods, so both forms are
        // the same read. That is what makes drift between them structurally impossible.
        self::assertSame($album->title(), $album->title);
        self::assertSame('Boards of Canada', $album->artist?->name);
        self::assertSame('Dawn Chorus', $album->tracks[1]->title);
    }

    public function testAttributesComeBackCoercedToNativeTypes(): void
    {
        $album = self::client(self::answering(self::collectionDocument()))
            ->albums->query()->get()[0];

        self::assertEquals(new \DateTimeImmutable('2002-02-18T00:00:00+00:00'), $album->releasedAt());
        self::assertSame(4.6, $album->averageRating());
        self::assertSame(AlbumStatus::Released, $album->status());
        self::assertSame('2002-02-18', $album->availableFrom()?->format('Y-m-d'));
        self::assertNull($album->availableUntil());
        self::assertSame(['label' => 'Warp', 'catalogueNumber' => 'WARPCD101'], $album->releaseInfo());
    }

    public function testReadingARelationTheReadDidNotIncludeThrows(): void
    {
        $album = self::client(self::answering(self::collectionDocument()))
            ->albums->query()->get()[0];

        // Always-safe companions still work: linkage is on the resource whether or not the
        // relation was hydrated.
        self::assertTrue($album->hasArtist());
        self::assertSame('7', $album->artistRef()?->id);
        self::assertCount(2, $album->tracksRef());

        // The static type here is AlbumBase, which has no `tracks()` at all — that case is
        // proved in MusicCatalogTypes. The runtime value is always an Album, which does, and
        // this is the path a caller takes after `with(...)`, after a conditional include, or
        // from any value whose static type was widened: check, then read, and get a throw that
        // names the fix rather than a null that looks like data.
        if (!$album instanceof Album) {
            self::fail('A read of albums should materialise an Album.');
        }

        $this->expectException(RelationNotIncludedException::class);
        $this->expectExceptionMessage('The "tracks" relation of "albums" was not included');

        $album->tracks();
    }

    public function testTheUnIncludedMessageNamesTheIncludePathAndTheSafeCompanion(): void
    {
        $album = self::client(self::answering(self::collectionDocument()))
            ->albums->query()->get()[0];

        if (!$album instanceof Album) {
            self::fail('A read of albums should materialise an Album.');
        }

        try {
            $album->artist();
            self::fail('Reading an un-included relation should have thrown.');
        } catch (RelationNotIncludedException $e) {
            self::assertSame('artist', $e->relation);
            self::assertSame('artist', $e->includePath);
            self::assertStringContainsString("->with('artist')", $e->getMessage());
            self::assertStringContainsString('->artistRef()', $e->getMessage());
        }
    }

    public function testReadingAnAttributeASparseFieldsetExcludedThrows(): void
    {
        $http = self::answering(self::collectionDocument(fields: ['title', 'status']));
        $album = self::client($http)->albums->query()->fields(['albums' => ['title', 'status']])->get()[0];

        self::assertStringContainsString('fields%5Balbums%5D=title%2Cstatus', (string) $http->lastRequest()->getUri());
        self::assertSame('Geogaddi', $album->title());

        try {
            $album->artwork();
            self::fail('Reading an excluded attribute should have thrown.');
        } catch (FieldNotSelectedException $e) {
            self::assertSame('artwork', $e->field);
            self::assertStringContainsString('fields[albums]=title,status', $e->getMessage());
        }
    }

    public function testNestedIncludesAreCheckedAtTheDepthTheySitAt(): void
    {
        $document = self::collectionDocument(
            extraIncluded: [self::albumResource('9', 'Music Has the Right to Children')],
        );

        $deep = self::client(self::answering($document))
            ->albums->query()->withTracks()->with('tracks.album')->get()[0];

        self::assertSame('Music Has the Right to Children', $deep->tracks[0]->album?->title());

        $shallow = self::client(self::answering($document))
            ->albums->query()->withTracks()->get()[0];

        try {
            $shallow->tracks[0]->album();
            self::fail('A nested relation that was not included should have thrown.');
        } catch (RelationNotIncludedException $e) {
            self::assertSame('tracks.album', $e->includePath);
        }
    }

    public function testFiltersSortPaginationAndCountAllReachTheWire(): void
    {
        $http = self::answering(self::collectionDocument());

        $albums = self::client($http)->albums->query()
            ->whereTitle('Geo')
            ->whereRating(['min' => 4.0])
            ->filter(['q' => 'boards', 'releasedAt' => ['min' => new \DateTimeImmutable('2000-01-01T00:00:00+00:00')]])
            ->filterRaw(['artist.name' => 'Boards of Canada'])
            ->sort('-releasedAt', 'title')
            ->sortRaw(['status'])
            ->page(2, 25)
            ->withCount(['tracks'])
            ->get();

        $uri = (string) $http->lastRequest()->getUri();

        self::assertStringContainsString('filter%5Btitle%5D=Geo', $uri);
        self::assertStringContainsString('filter%5Brating%5D%5Bmin%5D=4', $uri);
        self::assertStringContainsString('filter%5Bq%5D=boards', $uri);
        self::assertStringContainsString('filter%5BreleasedAt%5D%5Bmin%5D=2000-01-01T00%3A00%3A00%2B00%3A00', $uri);
        self::assertStringContainsString('filter%5Bartist.name%5D=Boards%20of%20Canada', $uri);
        self::assertStringContainsString('sort=-releasedAt%2Ctitle%2Cstatus', $uri);
        self::assertStringContainsString('page%5Bnumber%5D=2', $uri);
        self::assertStringContainsString('page%5Bsize%5D=25', $uri);
        self::assertStringContainsString('withCount=tracks', $uri);

        // The Countable profile is negotiated only because a `withCount` token was asked for.
        self::assertStringContainsString(
            'profile="https://haddowg.github.io/json-api/profiles/countable/"',
            $http->lastRequest()->getHeaderLine('Accept'),
        );

        // The page comes from the response, not from what was asked for.
        self::assertSame(1, $albums->_page()->number);
    }

    public function testACountedRelationIsReadThroughRelAndStillThrowsAsAValue(): void
    {
        $http = self::answering(self::collectionDocument());
        $album = self::client($http)->albums->query()->withCount(['tracks'])->get()[0];

        self::assertSame(23, $album->_rel('tracks')->total());
        self::assertSame('https://music.example/albums/1/tracks', $album->_rel('tracks')->related());

        if (!$album instanceof Album) {
            self::fail('A read of albums should materialise an Album.');
        }

        // Counting a relation is not including it, so the value still throws. That collision is
        // why `_rel()` earns its place: the count has somewhere to be read from.
        $this->expectException(RelationNotIncludedException::class);

        $album->tracks();
    }

    public function testTheThreeWriteInputFormsProduceOneDocument(): void
    {
        $expected = [
            'data' => [
                'type' => 'albums',
                'attributes' => [
                    'title' => 'Geogaddi',
                    'releasedAt' => '2002-02-18T00:00:00+00:00',
                    'status' => 'released',
                ],
                'relationships' => [
                    'artist' => ['data' => ['type' => 'artists', 'id' => '7']],
                ],
            ],
        ];

        $releasedAt = new \DateTimeImmutable('2002-02-18T00:00:00+00:00');

        $fromArray = self::answering(self::albumDocument());
        self::client($fromArray)->albums->create([
            'title' => 'Geogaddi',
            'releasedAt' => '2002-02-18T00:00:00+00:00',
            'status' => 'released',
            'artist' => Artist::ref('7'),
        ]);

        $fromDto = self::answering(self::albumDocument());
        self::client($fromDto)->albums->create(new AlbumCreate(
            title: 'Geogaddi',
            releasedAt: $releasedAt,
            status: AlbumStatus::Released,
            artist: Artist::ref('7'),
        ));

        $fromBuilder = self::answering(self::albumDocument());
        self::client($fromBuilder)->albums->create(
            Album::create()
                ->title('Geogaddi')
                ->releasedAt($releasedAt)
                ->status(AlbumStatus::Released)
                ->artist(Artist::ref('7')),
        );

        self::assertSame($expected, self::sentBody($fromArray));
        self::assertSame($expected, self::sentBody($fromDto));
        self::assertSame($expected, self::sentBody($fromBuilder));
    }

    public function testTheMissingSentinelKeepsAbsentDistinctFromAnExplicitNull(): void
    {
        $omitted = self::answering(self::albumDocument());
        self::client($omitted)->albums->id('1')->update(['title' => 'Geogaddi']);

        $cleared = self::answering(self::albumDocument());
        self::client($cleared)->albums->id('1')->update(['title' => 'Geogaddi', 'availableUntil' => null]);

        self::assertSame(
            ['data' => ['type' => 'albums', 'id' => '1', 'attributes' => ['title' => 'Geogaddi']]],
            self::sentBody($omitted),
        );
        self::assertSame(
            [
                'data' => [
                    'type' => 'albums',
                    'id' => '1',
                    'attributes' => ['title' => 'Geogaddi', 'availableUntil' => null],
                ],
            ],
            self::sentBody($cleared),
        );
    }

    public function testAWriteResponseIsProjectedByAClosure(): void
    {
        $http = self::answering(self::albumDocument(withArtist: true));

        $album = self::client($http)->albums->create(
            ['title' => 'Geogaddi'],
            fn($projection) => $projection->withArtist(),
        );

        self::assertStringContainsString('include=artist', (string) $http->lastRequest()->getUri());
        self::assertSame('Boards of Canada', $album->artist()?->name());
    }

    public function testAWriteResponseIsProjectedByAPrebuiltProjection(): void
    {
        $projection = Album::projection()->withArtist();

        $created = self::answering(self::albumDocument(withArtist: true));
        $createdAlbum = self::client($created)->albums->create(['title' => 'Geogaddi'], $projection);

        $updated = self::answering(self::albumDocument(withArtist: true));
        $updatedAlbum = self::client($updated)->albums->id('1')->update(['title' => 'Geogaddi'], $projection);

        self::assertSame('Boards of Canada', $createdAlbum->artist()?->name());
        self::assertSame('Boards of Canada', $updatedAlbum->artist()?->name());
        self::assertStringContainsString('include=artist', (string) $created->lastRequest()->getUri());
        self::assertStringContainsString('include=artist', (string) $updated->lastRequest()->getUri());
    }

    public function testEveryArrayDoorRejectsANameTheDescriptorDoesNotHave(): void
    {
        $client = self::client(self::answering(self::collectionDocument()));

        try {
            Album::create(['title' => 'Geogaddi', 'titel' => 'oops']);
            self::fail('An unknown write member should have been rejected.');
        } catch (UnknownAttributeException $e) {
            self::assertSame('titel', $e->member);
            self::assertStringContainsString('Did you mean "title"?', $e->getMessage());
        }

        try {
            $client->albums->query()->filter(['titel' => 'oops']);
            self::fail('An unknown filter name should have been rejected by the typed door.');
        } catch (UnknownFilterException $e) {
            self::assertStringContainsString('Did you mean "title"?', $e->getMessage());
        }

        try {
            $client->albums->query()->filterRaw(['titel' => 'oops']);
            self::fail('An unknown filter name should have been rejected by the loose door.');
        } catch (UnknownFilterException $e) {
            self::assertSame('titel', $e->filter);
        }

        // A nested range key sits in the same blind spot as a top-level one: an array shape
        // rejects a wrong value type inside a nested map and accepts an unknown key there, so
        // the guard has to reach one level down or `rating[minimum]` filters nothing.
        try {
            $client->albums->query()->filter(['rating' => ['minimum' => 4.0]]);
            self::fail('An unknown member of a structured filter should have been rejected.');
        } catch (UnknownFilterException $e) {
            self::assertSame('rating[minimum]', $e->filter);
            self::assertStringContainsString('Did you mean "rating[min]"?', $e->getMessage());
        }

        try {
            $client->albums->query()->sortRaw(['-titel']);
            self::fail('An unknown sort token should have been rejected.');
        } catch (UnknownSortException $e) {
            self::assertSame('-titel', $e->token);
            self::assertStringContainsString('Did you mean "-title"?', $e->getMessage());
        }

        try {
            $client->albums->query()->fields(['albums' => ['titel']]);
            self::fail('An unknown fieldset member should have been rejected.');
        } catch (UnknownFieldException $e) {
            self::assertSame('titel', $e->field);
        }
    }

    public function testTheBuilderRejectsAMissingRequiredMember(): void
    {
        try {
            Album::create()->status(AlbumStatus::Upcoming)->build();
            self::fail('Building without a required member should have thrown.');
        } catch (MissingRequiredMemberException $e) {
            self::assertSame(['title'], $e->members);
        }

        // Nothing is required on a patch, so the same omission is legal there.
        self::assertSame(
            ['data' => ['type' => 'albums', 'id' => '1']],
            Album::update()->build()->toDocument('1'),
        );
    }

    public function testRelationshipWritesSendTheLinkageTheDescriptorPermits(): void
    {
        $http = new RecordingHttpClient(
            new PsrResponse(204),
            new PsrResponse(204),
            new PsrResponse(204),
            new PsrResponse(204),
        );

        $album = self::client($http)->albums->id('1');

        $album->artist()->set(Artist::ref('7'));
        self::assertSame('PATCH', $http->lastRequest()->getMethod());
        self::assertSame('/albums/1/relationships/artist', $http->lastRequest()->getUri()->getPath());
        self::assertSame(['data' => ['type' => 'artists', 'id' => '7']], self::sentBody($http));

        $album->tracks()->add([Track::ref('11')]);
        self::assertSame('POST', $http->lastRequest()->getMethod());
        self::assertSame(['data' => [['type' => 'tracks', 'id' => '11']]], self::sentBody($http));

        $album->tracks()->remove([Track::ref('11')]);
        self::assertSame('DELETE', $http->lastRequest()->getMethod());

        $album->tracks()->replace([Track::ref('12'), Track::ref('13')]);
        self::assertSame('PATCH', $http->lastRequest()->getMethod());
        self::assertSame(
            ['data' => [['type' => 'tracks', 'id' => '12'], ['type' => 'tracks', 'id' => '13']]],
            self::sentBody($http),
        );
    }

    public function testCustomActionsFollowTheirDeclaredInputAndOutputShapes(): void
    {
        // input: document, output: document — the three write forms, returning the resource.
        $reissue = self::answering(self::albumDocument());
        $album = self::client($reissue)->albums->id('1')->actions()->reissue(['title' => 'Geogaddi']);

        self::assertSame('/albums/1/-actions/reissue', $reissue->lastRequest()->getUri()->getPath());
        self::assertSame('Geogaddi', $album->title());

        // input: raw + contentType, output: none.
        $artwork = new RecordingHttpClient(new PsrResponse(204));
        self::client($artwork)->albums->id('1')->actions()->artwork('binary-bytes');

        self::assertSame('/albums/1/-actions/artwork', $artwork->lastRequest()->getUri()->getPath());
        self::assertSame('application/octet-stream', $artwork->lastRequest()->getHeaderLine('Content-Type'));
        self::assertSame('binary-bytes', (string) $artwork->lastRequest()->getBody());

        // input: none, output: meta — collection-scoped, so it hangs off the type accessor.
        $summary = self::answering(['meta' => ['albums' => 2, 'tracks' => 23]]);
        $meta = self::client($summary)->albums->actions()->summary();

        self::assertSame('/albums/-actions/summary', $summary->lastRequest()->getUri()->getPath());
        self::assertSame(['albums' => 2, 'tracks' => 23], $meta);
    }

    public function testPaginationFollowsTheServersOwnLinks(): void
    {
        $first = self::collectionDocument(next: 'https://music.example/albums?page%5Bnumber%5D=2');
        $second = self::collectionDocument(
            albums: [self::albumResource('3', 'Tomorrow\'s Harvest')],
            currentPage: 2,
        );

        $http = new RecordingHttpClient(
            self::jsonResponse($first),
            self::jsonResponse($second),
        );

        $page = self::client($http)->albums->query()->page(1, 2)->get();

        self::assertSame(1, $page->_page()->number);
        self::assertTrue($page->_page()->hasNext());

        $next = $page->_next();

        self::assertNotNull($next);
        self::assertSame(2, $next->_page()->number);
        self::assertNull($next->_next());
    }

    public function testAutoPagingWalksEveryPageLazily(): void
    {
        $first = self::collectionDocument(next: 'https://music.example/albums?page%5Bnumber%5D=2');
        $second = self::collectionDocument(albums: [self::albumResource('3', 'Tomorrow\'s Harvest')]);

        $http = new RecordingHttpClient(self::jsonResponse($first), self::jsonResponse($second));

        $titles = [];

        foreach (self::client($http)->albums->query()->get()->_autoPaging() as $album) {
            $titles[] = $album->title();
        }

        self::assertSame(['Geogaddi', 'The Campfire Headphase', 'Tomorrow\'s Harvest'], $titles);
        self::assertCount(2, $http->requests);
    }

    public function testAConditionalIncludeIsAppliedAtRuntimeButNeverNarrows(): void
    {
        $http = self::answering(self::collectionDocument());

        $albums = self::client($http)->albums->query()
            ->when(true, fn($query) => $query->withArtist())
            ->get();

        // The request really does carry the include...
        self::assertStringContainsString('include=artist', (string) $http->lastRequest()->getUri());

        // ...and the relation really is hydrated. What does *not* happen is narrowing: the
        // projection is still AlbumBase, so `$albums[0]->artist` is a compile error and the
        // relation is reached through the companion or a runtime check. See MusicCatalogTypes.
        self::assertSame('7', $albums[0]->artistRef()?->id);
    }

    public function testTheDocumentIsSharedByReferenceAcrossEveryResourceInAResponse(): void
    {
        $album = self::client(self::answering(self::collectionDocument()))
            ->albums->query()->withArtist()->get()[0];

        self::assertSame($album->_document(), $album->artist()?->_document());
        self::assertSame('1.1', $album->_document()->jsonapi()->version);
        self::assertSame('https://music.example/albums/1', $album->_self());
    }

    private static function client(RecordingHttpClient $http): MusicCatalogClient
    {
        return new MusicCatalogClient(new ClientOptions(
            baseUrl: 'https://music.example',
            transport: $http,
            requestFactory: new Psr17Factory(),
            streamFactory: new Psr17Factory(),
        ));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function answering(array $document): RecordingHttpClient
    {
        return new RecordingHttpClient(self::jsonResponse($document));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function jsonResponse(array $document): PsrResponse
    {
        return new PsrResponse(
            200,
            ['Content-Type' => 'application/vnd.api+json'],
            \json_encode($document, \JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function sentBody(RecordingHttpClient $http): array
    {
        $decoded = \json_decode((string) $http->lastRequest()->getBody(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertIsArray($decoded);

        /** @var array<string, mixed> */
        return $decoded;
    }

    /**
     * @param list<string>|null                 $fields        a sparse fieldset applied to the albums
     * @param list<array<string, mixed>>        $extraIncluded resources beyond the artist and tracks
     * @param list<array<string, mixed>>|null   $albums        the primary data, when it is not the default pair
     *
     * @return array<string, mixed>
     */
    private static function collectionDocument(
        ?array $fields = null,
        array $extraIncluded = [],
        ?array $albums = null,
        int $currentPage = 1,
        ?string $next = null,
    ): array {
        $links = ['self' => 'https://music.example/albums'];

        if ($next !== null) {
            $links['next'] = $next;
        }

        return [
            'jsonapi' => ['version' => '1.1'],
            'data' => $albums ?? [
                self::albumResource('1', 'Geogaddi', $fields),
                self::albumResource('2', 'The Campfire Headphase', $fields),
            ],
            'included' => [
                self::artistResource(),
                self::trackResource('11', 'Ready Lets Go', 1),
                self::trackResource('12', 'Dawn Chorus', 2),
                ...$extraIncluded,
            ],
            'meta' => ['page' => ['currentPage' => $currentPage, 'perPage' => 2, 'from' => 1, 'to' => 2]],
            'links' => $links,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function albumDocument(bool $withArtist = false): array
    {
        $document = [
            'jsonapi' => ['version' => '1.1'],
            'data' => self::albumResource(),
            'links' => ['self' => 'https://music.example/albums/1'],
        ];

        if ($withArtist) {
            $document['included'] = [self::artistResource()];
        }

        return $document;
    }

    /**
     * @param list<string>|null $fields
     *
     * @return array<string, mixed>
     */
    private static function albumResource(string $id = '1', string $title = 'Geogaddi', ?array $fields = null): array
    {
        $attributes = [
            'title' => $title,
            'averageRating' => 4.6,
            'artwork' => 'https://cdn.music.example/' . $id . '.jpg',
            'releasedAt' => '2002-02-18T00:00:00+00:00',
            'explicit' => false,
            'status' => 'released',
            'availableFrom' => '2002-02-18',
            'availableUntil' => null,
            'releaseInfo' => ['label' => 'Warp', 'catalogueNumber' => 'WARPCD101'],
        ];

        if ($fields !== null) {
            $attributes = \array_intersect_key($attributes, \array_flip($fields));
        }

        return [
            'type' => 'albums',
            'id' => $id,
            'attributes' => $attributes,
            'relationships' => [
                'artist' => [
                    'data' => ['type' => 'artists', 'id' => '7'],
                    'links' => ['related' => 'https://music.example/albums/' . $id . '/artist'],
                ],
                'tracks' => [
                    'data' => [['type' => 'tracks', 'id' => '11'], ['type' => 'tracks', 'id' => '12']],
                    'meta' => ['total' => 23],
                    'links' => ['related' => 'https://music.example/albums/' . $id . '/tracks'],
                ],
            ],
            'links' => ['self' => 'https://music.example/albums/' . $id],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function artistResource(): array
    {
        return [
            'type' => 'artists',
            'id' => '7',
            'attributes' => [
                'name' => 'Boards of Canada',
                'slug' => 'boards-of-canada',
                'website' => null,
                'bio' => null,
                'trackCount' => 23,
                'createdAt' => '1995-01-01T00:00:00+00:00',
            ],
            'relationships' => ['albums' => ['data' => [['type' => 'albums', 'id' => '1']]]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function trackResource(string $id, string $title, int $number): array
    {
        return [
            'type' => 'tracks',
            'id' => $id,
            'attributes' => [
                'title' => $title,
                'trackNumber' => $number,
                'durationSeconds' => 240,
                'explicit' => false,
                'genres' => ['electronic'],
                'previewOffset' => null,
                'displayTitle' => $number . '. ' . $title,
            ],
            'relationships' => ['album' => ['data' => ['type' => 'albums', 'id' => '9']]],
        ];
    }
}
