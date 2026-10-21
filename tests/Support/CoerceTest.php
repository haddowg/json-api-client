<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Support;

use haddowg\JsonApiClient\Support\Coerce;
use haddowg\JsonApiClient\Support\Identifier;
use haddowg\JsonApiClient\Support\Missing;
use haddowg\JsonApiClient\Tests\Fixtures\AlbumStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Coerce::class)]
#[UsesClass(Identifier::class)]
final class CoerceTest extends TestCase
{
    public function testADateStringBecomesADateAndEverythingElsePassesThrough(): void
    {
        $date = new \DateTimeImmutable('2002-02-18T00:00:00+00:00');

        self::assertEquals($date, Coerce::dateTime('2002-02-18T00:00:00+00:00'));
        self::assertSame($date, Coerce::dateTime($date));
        self::assertSame(Missing::Value, Coerce::dateTime(Missing::Value));
    }

    public function testANullableDateKeepsNullDistinctFromMissing(): void
    {
        self::assertNull(Coerce::nullableDateTime(null));
        self::assertSame(Missing::Value, Coerce::nullableDateTime(Missing::Value));
        self::assertEquals(new \DateTimeImmutable('2002-02-18'), Coerce::nullableDateTime('2002-02-18'));
    }

    public function testABackingValueBecomesItsEnumCase(): void
    {
        self::assertSame(AlbumStatus::Published, Coerce::enum(AlbumStatus::class, 'published'));
        self::assertSame(AlbumStatus::Draft, Coerce::enum(AlbumStatus::class, AlbumStatus::Draft));
        self::assertSame(Missing::Value, Coerce::enum(AlbumStatus::class, Missing::Value));
    }

    public function testAnUnknownBackingValueIsRejected(): void
    {
        $this->expectException(\ValueError::class);

        Coerce::enum(AlbumStatus::class, 'archived');
    }

    public function testLinkageIsAnIdentifierObjectOrNull(): void
    {
        self::assertSame(['type' => 'artists', 'id' => '1'], Coerce::linkage(new Identifier('artists', '1')));
        self::assertNull(Coerce::linkage(null));
    }

    public function testToManyLinkageKeepsItsOrder(): void
    {
        self::assertSame(
            [['type' => 'tracks', 'id' => '2'], ['type' => 'tracks', 'id' => '1']],
            Coerce::linkageList([new Identifier('tracks', '2'), new Identifier('tracks', '1')]),
        );
    }
}
