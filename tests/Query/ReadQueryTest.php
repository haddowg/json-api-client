<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Query;

use haddowg\JsonApiClient\Query\ReadQuery;
use haddowg\JsonApiClient\Tests\Fixtures\AlbumStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReadQuery::class)]
#[UsesClass(AlbumStatus::class)]
final class ReadQueryTest extends TestCase
{
    public function testAnEmptyQuerySerialisesToNothing(): void
    {
        $query = new ReadQuery();

        self::assertTrue($query->isEmpty());
        self::assertSame('', $query->toQueryString());
        self::assertSame('/albums', $query->appendTo('/albums'));
    }

    public function testBracketedKeysStayLiteralAndOnlyValuesAreEncoded(): void
    {
        // Servers match on the literal bracketed key; encoding it would silently miss.
        $query = new ReadQuery(filter: ['title' => 'Music Has The Right']);

        self::assertSame('filter[title]=Music%20Has%20The%20Right', $query->toQueryString());
    }

    public function testTheFamilyOrderIsFixedSoTheSameQueryAlwaysProducesTheSameUrl(): void
    {
        $query = new ReadQuery(
            filter: ['title' => 'a'],
            sort: ['-releasedAt', 'title'],
            include: ['artist', 'tracks'],
            fields: ['albums' => ['title']],
            withCount: ['_self_', 'tracks'],
            page: ['number' => 2, 'size' => 10],
        );

        self::assertSame(
            'filter[title]=a&sort=-releasedAt%2Ctitle&include=artist%2Ctracks&fields[albums]=title'
            . '&withCount=_self_%2Ctracks&page[number]=2&page[size]=10',
            $query->toQueryString(),
        );
    }

    public function testAStructuredFilterValueRecursesIntoNestedBracketedKeys(): void
    {
        // Without this a range would reach the wire as `filter[releasedAt]=Array`.
        $query = new ReadQuery(filter: ['releasedAt' => ['min' => '1998-01-01', 'max' => '2002-12-31']]);

        self::assertSame(
            'filter[releasedAt][min]=1998-01-01&filter[releasedAt][max]=2002-12-31',
            $query->toQueryString(),
        );
    }

    public function testAListFilterValueIsCommaJoined(): void
    {
        $query = new ReadQuery(filter: ['genres' => ['ambient', 'idm']]);

        self::assertSame('filter[genres]=ambient%2Cidm', $query->toQueryString());
    }

    public function testABooleanGoesOverTheWireAsTrueNotAsOne(): void
    {
        // `filter[approved]=1` is not the filter the server documented.
        $query = new ReadQuery(filter: ['approved' => true, 'archived' => false]);

        self::assertSame('filter[approved]=true&filter[archived]=false', $query->toQueryString());
    }

    public function testAnExplicitlyEmptyFieldsetIsMeaningfulAndIsSent(): void
    {
        // It selects no members of that type, which is not the same as not asking.
        $query = new ReadQuery(fields: ['albums' => [], 'artists' => ['name']]);

        self::assertSame('fields[albums]=&fields[artists]=name', $query->toQueryString());
    }

    public function testEmptyAndNullValuesAreSkippedEverywhereElse(): void
    {
        $query = new ReadQuery(filter: ['title' => null, 'slug' => '', 'q' => 'ok'], page: ['size' => null]);

        self::assertSame('filter[q]=ok', $query->toQueryString());
    }

    public function testEnumsAndDatesUseTheirWireForm(): void
    {
        $query = new ReadQuery(filter: [
            'status' => AlbumStatus::Published,
            'releasedAt' => new \DateTimeImmutable('2002-02-18T00:00:00+00:00'),
        ]);

        self::assertSame(
            'filter[status]=published&filter[releasedAt]=2002-02-18T00%3A00%3A00%2B00%3A00',
            $query->toQueryString(),
        );
    }

    public function testAValueWithNoDefensibleWireFormIsDroppedRatherThanMangled(): void
    {
        $query = new ReadQuery(filter: ['title' => new \stdClass(), 'q' => 'ok']);

        self::assertSame('filter[q]=ok', $query->toQueryString());
    }

    public function testAppendingToAUriPicksTheRightSeparator(): void
    {
        $query = new ReadQuery(include: ['artist']);

        self::assertSame('/albums?include=artist', $query->appendTo('/albums'));
        self::assertSame('/albums?x=1&include=artist', $query->appendTo('/albums?x=1'));
    }

    public function testIsEmptyIsFalseAsSoonAsAnyFamilyIsPopulated(): void
    {
        self::assertFalse((new ReadQuery(filter: ['a' => 1]))->isEmpty());
        self::assertFalse((new ReadQuery(sort: ['a']))->isEmpty());
        self::assertFalse((new ReadQuery(include: ['a']))->isEmpty());
        self::assertFalse((new ReadQuery(fields: ['a' => []]))->isEmpty());
        self::assertFalse((new ReadQuery(withCount: ['a']))->isEmpty());
        self::assertFalse((new ReadQuery(page: ['number' => 1]))->isEmpty());
    }
}
