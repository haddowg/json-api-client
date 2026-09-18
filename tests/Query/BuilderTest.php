<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Query;

use haddowg\JsonApiClient\Query\Projection;
use haddowg\JsonApiClient\Query\Query;
use haddowg\JsonApiClient\Query\ReadQuery;
use haddowg\JsonApiClient\Tests\Fixtures\AlbumBase;
use haddowg\JsonApiClient\Tests\Fixtures\AlbumHasArtist;
use haddowg\JsonApiClient\Tests\Fixtures\AlbumProjection;
use haddowg\JsonApiClient\Tests\Fixtures\AlbumQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Projection::class)]
#[CoversClass(Query::class)]
#[UsesClass(ReadQuery::class)]
#[UsesClass(AlbumProjection::class)]
#[UsesClass(AlbumQuery::class)]
final class BuilderTest extends TestCase
{
    public function testAProjectionCarriesOnlyIncludeAndFields(): void
    {
        // A write response honours nothing else, and a stray `sort` on a POST is a 400.
        $query = AlbumProjection::make()->withArtist()->fields(['albums' => ['title']])->toReadQuery();

        self::assertSame('include=artist&fields[albums]=title', $query->toQueryString());
        self::assertSame([], $query->sort);
        self::assertSame([], $query->filter);
        self::assertSame([], $query->page);
    }

    public function testEveryStepIsImmutableSoAProjectionIsSafeToReuse(): void
    {
        $base = AlbumProjection::make();
        $withArtist = $base->withArtist();

        self::assertSame('', $base->toReadQuery()->toQueryString());
        self::assertSame('include=artist', $withArtist->toReadQuery()->toQueryString());
        self::assertNotSame($base, $withArtist);
    }

    public function testNarrowingPreservesTheSubclassSoTheChainSurvivesInEitherOrder(): void
    {
        // What makes codegen able to share `withX()` between the projection and the query.
        $forwards = AlbumQuery::make()->withArtist()->sort('-releasedAt');
        $backwards = AlbumQuery::make()->sort('-releasedAt')->withArtist();

        self::assertInstanceOf(AlbumQuery::class, $forwards);
        self::assertInstanceOf(AlbumQuery::class, $backwards);
        self::assertSame(
            $forwards->toReadQuery()->toQueryString(),
            $backwards->toReadQuery()->toQueryString(),
        );
    }

    public function testTheNarrowedProjectionIsTheIntersectionOfTheMarkers(): void
    {
        $narrowed = AlbumQuery::make()->withArtist();

        self::assertSame('include=artist', $narrowed->toReadQuery()->toQueryString());
        self::assertTrue(self::acceptsAnArtist($narrowed));
    }

    public function testTheUntypedIncludeDoorCarriesDeepAndProgrammaticPaths(): void
    {
        $paths = ['tracks.album', 'artist'];

        $query = AlbumQuery::make()->with(...$paths)->toReadQuery();

        self::assertSame(['tracks.album', 'artist'], $query->include);
    }

    public function testIncludePathsAreNotRepeated(): void
    {
        $query = AlbumQuery::make()->withArtist()->with('artist')->withArtist()->toReadQuery();

        self::assertSame(['artist'], $query->include);
    }

    public function testFieldsetsMergePerTypeWithTheLastWriteWinning(): void
    {
        $query = AlbumQuery::make()
            ->fields(['albums' => ['title']])
            ->fields(['artists' => ['name']])
            ->fields(['albums' => ['title', 'releasedAt']])
            ->toReadQuery();

        self::assertSame(['albums' => ['title', 'releasedAt'], 'artists' => ['name']], $query->fields);
    }

    public function testSortTokensAccumulateInPrecedenceOrder(): void
    {
        // Argument order is precedence; there is nothing to translate on the way to the wire.
        $query = AlbumQuery::make()->sort('-releasedAt')->sort('title')->toReadQuery();

        self::assertSame(['-releasedAt', 'title'], $query->sort);
        self::assertSame('sort=-releasedAt%2Ctitle', $query->toQueryString());
    }

    public function testTheLooseSortDoorTakesTokensBuiltAtRuntime(): void
    {
        $fromRequest = ['-releasedAt', 'title'];

        self::assertSame($fromRequest, AlbumQuery::make()->sortRaw($fromRequest)->toReadQuery()->sort);
    }

    public function testFiltersMergeWithTheLastWriteWinningPerName(): void
    {
        $query = AlbumQuery::make()
            ->whereTitle('Geogaddi')
            ->filterRaw(['artist.name' => 'Boards of Canada'])
            ->whereTitle('Twoism')
            ->toReadQuery();

        self::assertSame(['title' => 'Twoism', 'artist.name' => 'Boards of Canada'], $query->filter);
    }

    public function testCountTokensAreNotRepeated(): void
    {
        $query = AlbumQuery::make()->withCount(['_self_', 'tracks'])->withCount(['tracks'])->toReadQuery();

        self::assertSame(['_self_', 'tracks'], $query->withCount);
    }

    public function testPaginationParametersMergeSoASizeCanBeSetSeparately(): void
    {
        $query = AlbumQuery::make()->page(2, 10)->page(3)->toReadQuery();

        self::assertSame(['number' => 3, 'size' => 10], $query->page);
    }

    public function testAFullReadCarriesEveryFamily(): void
    {
        $query = AlbumQuery::make()
            ->withArtist()
            ->withTracks()
            ->whereTitle('Geogaddi')
            ->sort('-releasedAt')
            ->withCount(['_self_'])
            ->fields(['albums' => ['title']])
            ->page(2, 10)
            ->toReadQuery();

        self::assertSame(
            'filter[title]=Geogaddi&sort=-releasedAt&include=artist%2Ctracks&fields[albums]=title'
            . '&withCount=_self_&page[number]=2&page[size]=10',
            $query->toQueryString(),
        );
    }

    public function testABuilderWithNothingSetProducesAnEmptyQuery(): void
    {
        self::assertTrue(AlbumQuery::make()->toReadQuery()->isEmpty());
        self::assertTrue(AlbumProjection::make()->toReadQuery()->isEmpty());
    }

    /**
     * Only ever type-checked: the parameter is the narrowed projection, so this call compiles
     * for a builder that included the artist and for no other.
     *
     * @param AlbumQuery<AlbumBase&AlbumHasArtist> $query
     */
    private static function acceptsAnArtist(AlbumQuery $query): bool
    {
        return $query->toReadQuery()->include === ['artist'];
    }
}
