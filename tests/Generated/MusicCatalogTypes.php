<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated;

use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\Album;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumStatus;
use haddowg\JsonApiClient\Tests\Generated\MusicCatalog\MusicCatalogClient;

/**
 * What the generated surface promises the type checker, asserted rather than described.
 *
 * Analysed, never executed: `PHPStan\Testing\assertType()` is understood by the analyser and
 * does not exist at runtime, so these methods are deliberately called by nothing. A mismatch is
 * a non-ignorable `phpstan.type` error, which makes this file a contract the emitters have to
 * satisfy rather than a comment they can drift from.
 *
 * The *negative* cases — the calls that must not compile — are recorded in the README with the
 * exact message level 9 produces, because leaving a failing expression in a file that has to
 * stay green is not an option. Each was confirmed by adding it, running PHPStan, and removing
 * it again.
 */
final class MusicCatalogTypes
{
    public function narrowingAtDepthOne(MusicCatalogClient $client): void
    {
        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumQuery<haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase>',
            $client->albums->query(),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumQuery<haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasArtist>',
            $client->albums->query()->withArtist(),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Collections\PaginatedCollection<haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasArtist, haddowg\JsonApiClient\Pagination\PageNumber>',
            $client->albums->query()->withArtist()->get(),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasArtist',
            $client->albums->query()->withArtist()->get()[0],
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\Artist|null',
            $client->albums->query()->withArtist()->get()[0]->artist,
        );

        \PHPStan\Testing\assertType(
            '(haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasArtist)|null',
            $client->albums->query()->withArtist()->first(),
        );
    }

    /**
     * The narrowing survives every other builder call, in either order along the chain. That is
     * what lets the `withX()` methods live in one shared trait instead of being emitted once per
     * builder class.
     */
    public function narrowingSurvivesTheRestOfTheChain(MusicCatalogClient $client): void
    {
        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumQuery<haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasArtist&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasTracks>',
            $client->albums->query()->withArtist()->sort('title')->page(1)->withTracks(),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumQuery<haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasArtist&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasTracks>',
            $client->albums->query()->withTracks()->withArtist()->whereTitle('Geo')->withCount(['tracks']),
        );
    }

    /**
     * Depth 2 is declared and not narrowed: a track's `album` is reachable from any track, and
     * whether it is really there is a runtime question.
     */
    public function depthTwoIsDeclaredNotNarrowed(MusicCatalogClient $client): void
    {
        $tracks = $client->albums->query()->withTracks()->get()[0]->tracks;

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Collections\ResourceCollection<haddowg\JsonApiClient\Tests\Generated\MusicCatalog\Track>',
            $tracks,
        );

        \PHPStan\Testing\assertType('haddowg\JsonApiClient\Tests\Generated\MusicCatalog\Album|null', $tracks[0]->album);
    }

    /**
     * A runtime condition cannot produce a compile-time projection, so `when()` erases the
     * narrowing the closure applied. The include really is sent — see the usage test — but the
     * relation is reachable only through the companions or a runtime check.
     */
    public function aConditionalIncludeDoesNotNarrow(MusicCatalogClient $client, bool $flag): void
    {
        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumQuery<haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase>',
            $client->albums->query()->when($flag, fn($query) => $query->withArtist()),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumQuery<haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase>',
            $client->albums->query()->with('artist'),
        );
    }

    /**
     * The arity overload on the static factories, and the three write input forms behind it.
     */
    public function theWriteFactoriesOverloadOnArity(): void
    {
        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumCreateBuilder',
            Album::create(),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumCreate',
            Album::create(['title' => 'Geogaddi', 'status' => AlbumStatus::Released]),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumUpdateBuilder',
            Album::update(),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumUpdate',
            Album::update(['title' => 'Geogaddi']),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumProjection<haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasArtist>',
            Album::projection()->withArtist(),
        );
    }

    /**
     * A write response is projected by a closure or by a prebuilt projection, and by neither.
     *
     * The omitted case is why the return type is conditional: a plain `AlbumBase&TProj` cannot
     * resolve `TProj` when there is no projection to infer it from.
     */
    public function writeResponsesAreProjectedBothWays(MusicCatalogClient $client): void
    {
        $input = Album::create(['title' => 'Geogaddi']);
        $projection = Album::projection()->withArtist()->withTracks();

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase',
            $client->albums->create($input),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasArtist',
            $client->albums->create($input, fn($p) => $p->withArtist()),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasArtist&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasTracks',
            $client->albums->create($input, $projection),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasArtist&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasTracks',
            $client->albums->id('1')->update(Album::update(['title' => 'x']), $projection),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasArtist',
            $client->albums->id('1')->get(fn($p) => $p->withArtist()),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumBase&haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumHasArtist',
            $client->albums->id('1')->actions()->reissue($input, fn($p) => $p->withArtist()),
        );
    }

    /**
     * Attribute values are the coerced native types, and the paginator is part of the collection
     * type rather than a nullable field on it.
     */
    public function attributesAndPagesAreConcrete(MusicCatalogClient $client): void
    {
        $albums = $client->albums->query()->get();
        $album = $albums[0];

        \PHPStan\Testing\assertType('haddowg\JsonApiClient\Pagination\PageNumber', $albums->_page());
        \PHPStan\Testing\assertType('int|null', $albums->_page()->total);
        \PHPStan\Testing\assertType('string', $album->title);
        \PHPStan\Testing\assertType('float|null', $album->averageRating);
        \PHPStan\Testing\assertType('DateTimeImmutable', $album->releasedAt);
        \PHPStan\Testing\assertType('DateTimeImmutable|null', $album->availableFrom);
        \PHPStan\Testing\assertType('haddowg\JsonApiClient\Tests\Generated\MusicCatalog\AlbumStatus', $album->status);
        \PHPStan\Testing\assertType(
            'array{label?: string, catalogueNumber?: string}|null',
            $album->releaseInfo,
        );
        \PHPStan\Testing\assertType('haddowg\JsonApiClient\Support\Identifier|null', $album->artistRef());
        \PHPStan\Testing\assertType('haddowg\JsonApiClient\Resources\Relationship', $album->_rel('tracks'));
        \PHPStan\Testing\assertType('int|null', $album->_rel('tracks')->total());
    }

    /**
     * The relationship handles expose the verbs the descriptor permits and no others. `set()`
     * on a to-one, all three write verbs on a to-many whose endpoint declares all three.
     */
    public function relationshipHandlesExposeThePermittedVerbs(MusicCatalogClient $client): void
    {
        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Support\Identifier|null',
            $client->albums->id('1')->artist()->get(),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Tests\Generated\MusicCatalog\Artist|null',
            $client->albums->id('1')->artist()->related(),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Collections\PaginatedCollection<haddowg\JsonApiClient\Support\Identifier, haddowg\JsonApiClient\Pagination\PageNumber>',
            $client->albums->id('1')->tracks()->get(),
        );

        \PHPStan\Testing\assertType(
            'haddowg\JsonApiClient\Collections\PaginatedCollection<haddowg\JsonApiClient\Tests\Generated\MusicCatalog\Track, haddowg\JsonApiClient\Pagination\PageNumber>',
            $client->albums->id('1')->tracks()->related(),
        );

        \PHPStan\Testing\assertType('array<string, mixed>', $client->albums->actions()->summary());
    }
}
