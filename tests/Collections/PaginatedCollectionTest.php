<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Collections;

use haddowg\JsonApiClient\Collections\PaginatedCollection;
use haddowg\JsonApiClient\Collections\ResourceCollection;
use haddowg\JsonApiClient\Pagination\Page;
use haddowg\JsonApiClient\Pagination\PageLinks;
use haddowg\JsonApiClient\Pagination\PageNumber;
use haddowg\JsonApiClient\Pagination\PaginatorKind;
use haddowg\JsonApiClient\Support\Wire;
use haddowg\JsonApiClient\Tests\Fixtures\AlbumBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaginatedCollection::class)]
#[UsesClass(ResourceCollection::class)]
#[UsesClass(Page::class)]
#[UsesClass(PageNumber::class)]
#[UsesClass(PageLinks::class)]
#[UsesClass(Wire::class)]
#[UsesClass(AlbumBase::class)]
final class PaginatedCollectionTest extends TestCase
{
    public function testItIsStillAResourceCollection(): void
    {
        $collection = self::page(1, ['1', '2']);

        self::assertInstanceOf(ResourceCollection::class, $collection);
        self::assertCount(2, $collection);
        self::assertSame('1', $collection[0]->id);
    }

    public function testThePageIsTheExactPaginatorKindAndIsNeverNull(): void
    {
        $collection = self::page(2, ['3']);

        self::assertInstanceOf(PageNumber::class, $collection->_page());
        self::assertSame(2, $collection->_page()->number);
        self::assertSame(PaginatorKind::PageNumber, $collection->_page()->kind());
    }

    public function testNavigationFollowsTheServersLinks(): void
    {
        $collection = self::page(2, ['3', '4']);

        $next = $collection->_next();
        $prev = $collection->_prev();

        self::assertInstanceOf(PaginatedCollection::class, $next);
        self::assertInstanceOf(PaginatedCollection::class, $prev);
        self::assertSame(3, $next->_page()->number);
        self::assertSame(1, $prev->_page()->number);
    }

    public function testNavigationStopsAtTheEndsRatherThanFetchingNothing(): void
    {
        $first = self::page(1, ['1']);
        $last = self::page(3, ['5']);

        self::assertNull($first->_prev());
        self::assertNull($last->_next());
    }

    public function testAutoPagingWalksEveryRemainingPageLazily(): void
    {
        $fetched = [];
        $collection = self::page(1, ['1', '2'], $fetched);

        $ids = [];
        foreach ($collection->_autoPaging() as $album) {
            $ids[] = $album->id;
        }

        self::assertSame(['1', '2', '3', '4', '5'], $ids);
        self::assertSame(
            ['/albums?page[number]=2', '/albums?page[number]=3'],
            $fetched,
            'the first page is already in hand, so only the pages after it are fetched',
        );
    }

    public function testAutoPagingKeysRunUnbrokenAcrossPageBoundaries(): void
    {
        $members = \iterator_to_array(self::page(1, ['1', '2'])->_autoPaging());

        self::assertSame([0, 1, 2, 3, 4], \array_keys($members));
    }

    public function testAutoPagingFetchesNothingUntilItIsAskedTo(): void
    {
        $fetched = [];
        $generator = self::page(1, ['1', '2'], $fetched)->_autoPaging();

        self::assertSame([], $fetched);

        // Two members are already in hand, so the second page is only reached on the third.
        $generator->current();
        self::assertSame([], $fetched);

        $generator->next();
        $generator->next();
        self::assertSame(['/albums?page[number]=2'], $fetched);
    }

    public function testAutoPagingOverASinglePageFetchesNothing(): void
    {
        $collection = new PaginatedCollection(
            [new AlbumBase('1', 'Album 1')],
            self::pageNumber(1, []),
        );

        self::assertCount(1, \iterator_to_array($collection->_autoPaging()));
    }

    public function testFollowingALinkWithNoNavigatorIsAProgrammingErrorNotASilentNull(): void
    {
        $collection = new PaginatedCollection(
            [new AlbumBase('1', 'Album 1')],
            self::pageNumber(1, ['next' => '/albums?page[number]=2']),
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('it was built without a navigator');

        $collection->_next();
    }

    public function testTheCollectionLevelMembersStillWork(): void
    {
        $collection = new PaginatedCollection(
            [new AlbumBase('1', 'Album 1')],
            self::pageNumber(1, []),
            ['page' => ['total' => 1]],
            ['self' => '/albums'],
        );

        self::assertSame(['page' => ['total' => 1]], $collection->_meta());
        self::assertSame(['self' => '/albums'], $collection->_links());
    }

    /**
     * Three pages of the same collection, navigable in both directions.
     *
     * @param list<string>  $ids
     * @param list<string>  $fetched records the links the navigator was asked to follow
     *
     * @return PaginatedCollection<AlbumBase, PageNumber>
     */
    private static function page(int $number, array $ids, array &$fetched = []): PaginatedCollection
    {
        $links = [];

        if ($number > 1) {
            $links['prev'] = '/albums?page[number]=' . ($number - 1);
        }

        if ($number < 3) {
            $links['next'] = '/albums?page[number]=' . ($number + 1);
        }

        $contents = [1 => ['1', '2'], 2 => ['3', '4'], 3 => ['5']];

        return new PaginatedCollection(
            \array_map(static fn(string $id): AlbumBase => new AlbumBase($id, 'Album ' . $id), $ids),
            self::pageNumber($number, $links),
            [],
            $links,
            static function (string $url) use (&$fetched, $contents): PaginatedCollection {
                $fetched[] = $url;
                $target = (int) (PageLinks::param($url, 'number') ?? '1');

                return self::page($target, $contents[$target] ?? [], $fetched);
            },
        );
    }

    /**
     * @param array<string, mixed> $links
     */
    private static function pageNumber(int $number, array $links): PageNumber
    {
        $page = Page::for(PaginatorKind::PageNumber, ['page' => ['currentPage' => $number]], $links);

        self::assertInstanceOf(PageNumber::class, $page);

        return $page;
    }
}
