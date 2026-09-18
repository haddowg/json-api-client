<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Pagination;

use haddowg\JsonApiClient\Pagination\Cursor;
use haddowg\JsonApiClient\Pagination\Offset;
use haddowg\JsonApiClient\Pagination\Page;
use haddowg\JsonApiClient\Pagination\PageLinks;
use haddowg\JsonApiClient\Pagination\PageNumber;
use haddowg\JsonApiClient\Pagination\PaginatorKind;
use haddowg\JsonApiClient\Support\Wire;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Page::class)]
#[CoversClass(PageNumber::class)]
#[CoversClass(Offset::class)]
#[CoversClass(Cursor::class)]
#[CoversClass(PageLinks::class)]
#[CoversClass(PaginatorKind::class)]
#[UsesClass(Wire::class)]
final class PageTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private const array PAGE_LINKS = [
        'self' => '/albums?page[number]=2&page[size]=10',
        'first' => '/albums?page[number]=1&page[size]=10',
        'prev' => '/albums?page[number]=1&page[size]=10',
        'next' => ['href' => '/albums?page[number]=3&page[size]=10'],
        'last' => '/albums?page[number]=5&page[size]=10',
    ];

    public function testACountedPageNumberedCollection(): void
    {
        $page = Page::for(
            PaginatorKind::PageNumber,
            ['page' => ['currentPage' => 2, 'perPage' => 10, 'from' => 11, 'to' => 20, 'total' => 47, 'lastPage' => 5]],
            self::PAGE_LINKS,
        );

        self::assertInstanceOf(PageNumber::class, $page);
        self::assertSame(PaginatorKind::PageNumber, $page->kind());
        self::assertSame(2, $page->number);
        self::assertSame(10, $page->size);
        self::assertSame(11, $page->from);
        self::assertSame(20, $page->to);
        self::assertSame(47, $page->total);
        self::assertSame(5, $page->lastPage);
        self::assertTrue($page->isCounted());
    }

    public function testCountFreeModeLeavesTotalAndLastPageNullWithoutBreakingNavigation(): void
    {
        // Counting is opt-in, so this is the normal response, not a degraded one.
        $page = Page::for(
            PaginatorKind::PageNumber,
            ['page' => ['currentPage' => 2, 'perPage' => 10, 'from' => 11]],
            ['first' => '/albums', 'prev' => '/albums?page[number]=1', 'next' => '/albums?page[number]=3'],
        );

        self::assertInstanceOf(PageNumber::class, $page);
        self::assertNull($page->total);
        self::assertNull($page->lastPage);
        self::assertFalse($page->isCounted());
        self::assertTrue($page->hasNext());
        self::assertTrue($page->hasPrev());
        self::assertNull($page->last(), 'a server that does not count cannot say which page is last');
    }

    public function testTheLinksAreResolvedThroughBothFormsAndExposedOnTheBase(): void
    {
        $page = Page::for(PaginatorKind::PageNumber, [], self::PAGE_LINKS);

        self::assertInstanceOf(Page::class, $page);
        self::assertSame('/albums?page[number]=1&page[size]=10', $page->first());
        self::assertSame('/albums?page[number]=1&page[size]=10', $page->prev());
        self::assertSame('/albums?page[number]=3&page[size]=10', $page->next());
        self::assertSame('/albums?page[number]=5&page[size]=10', $page->last());
    }

    public function testTheRawPageMetaStaysReachableForAnythingTheKindDoesNotModel(): void
    {
        $page = Page::for(PaginatorKind::PageNumber, ['page' => ['currentPage' => 1, 'vendorExtra' => 'x']], []);

        self::assertInstanceOf(Page::class, $page);
        self::assertSame(['currentPage' => 1, 'vendorExtra' => 'x'], $page->meta());
    }

    public function testAnOffsetCollection(): void
    {
        $page = Page::for(
            PaginatorKind::Offset,
            ['page' => ['offset' => 20, 'limit' => 10, 'from' => 21, 'to' => 30, 'total' => 47]],
            ['next' => '/albums?page[offset]=30&page[limit]=10'],
        );

        self::assertInstanceOf(Offset::class, $page);
        self::assertSame(PaginatorKind::Offset, $page->kind());
        self::assertSame(20, $page->offset);
        self::assertSame(10, $page->limit);
        self::assertSame(21, $page->from);
        self::assertSame(30, $page->to);
        self::assertSame(47, $page->total);
        self::assertTrue($page->isCounted());
    }

    public function testAnUncountedOffsetCollection(): void
    {
        $page = Page::for(PaginatorKind::Offset, ['page' => ['offset' => 0, 'limit' => 10]], []);

        self::assertInstanceOf(Offset::class, $page);
        self::assertNull($page->total);
        self::assertFalse($page->isCounted());
    }

    public function testACursorCollectionReadsItsResumeCursorsOutOfTheLinks(): void
    {
        // The server states the cursor to resume from in the next/prev links and nowhere else.
        $page = Page::for(
            PaginatorKind::Cursor,
            ['page' => ['perPage' => 10, 'from' => 'aaa', 'to' => 'zzz', 'hasMore' => true]],
            [
                'first' => '/tracks?page[size]=10',
                'prev' => '/tracks?page[before]=aaa&page[size]=10',
                'next' => '/tracks?page[after]=zzz&page[size]=10',
            ],
        );

        self::assertInstanceOf(Cursor::class, $page);
        self::assertSame(PaginatorKind::Cursor, $page->kind());
        self::assertSame('zzz', $page->after);
        self::assertSame('aaa', $page->before);
        self::assertSame('aaa', $page->from);
        self::assertSame('zzz', $page->to);
        self::assertSame(10, $page->size);
        self::assertTrue($page->hasMore);
        self::assertNull($page->last(), 'cursor pagination never counts, so there is no last page');
    }

    public function testACursorCollectionFallsBackToTheNextLinkWhenTheServerOmitsHasMore(): void
    {
        $withLink = Page::for(PaginatorKind::Cursor, [], ['next' => '/tracks?page[after]=zzz']);
        $withoutLink = Page::for(PaginatorKind::Cursor, [], []);

        self::assertInstanceOf(Cursor::class, $withLink);
        self::assertInstanceOf(Cursor::class, $withoutLink);
        self::assertTrue($withLink->hasMore);
        self::assertFalse($withoutLink->hasMore);
        self::assertNull($withoutLink->after);
    }

    public function testACursorBoundaryMayArriveAsANumericRowKey(): void
    {
        $page = Page::for(PaginatorKind::Cursor, ['page' => ['from' => 101, 'to' => 110]], []);

        self::assertInstanceOf(Cursor::class, $page);
        self::assertSame('101', $page->from);
        self::assertSame('110', $page->to);
    }

    public function testAnUnpaginatedCollectionHasNoPageAtAll(): void
    {
        // Which is why `_page()` is absent on one rather than returning null.
        self::assertNull(Page::for(PaginatorKind::None, ['page' => ['total' => 3]], self::PAGE_LINKS));
    }

    public function testAPageLinkParameterIsReadOutOfTheQueryString(): void
    {
        self::assertSame('zzz', PageLinks::param('/tracks?page[after]=zzz&page[size]=10', 'after'));
        self::assertSame('10', PageLinks::param('/tracks?page[after]=zzz&page[size]=10', 'size'));
        self::assertNull(PageLinks::param('/tracks?page[after]=zzz', 'before'));
        self::assertNull(PageLinks::param('/tracks', 'after'));
        self::assertNull(PageLinks::param('/tracks?sort=title', 'after'));
        self::assertNull(PageLinks::param(null, 'after'));
    }

    public function testPageLinkParametersSurviveAnAbsoluteUrl(): void
    {
        self::assertSame(
            'zzz',
            PageLinks::param('https://api.example.com/v1/tracks?page%5Bafter%5D=zzz', 'after'),
        );
    }

    public function testTheKindsMatchTheWireVocabulary(): void
    {
        self::assertSame('page', PaginatorKind::PageNumber->value);
        self::assertSame('offset', PaginatorKind::Offset->value);
        self::assertSame('cursor', PaginatorKind::Cursor->value);
        self::assertSame('none', PaginatorKind::None->value);
    }
}
