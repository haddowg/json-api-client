<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Resources;

use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Resources\Pivot;
use haddowg\JsonApiClient\Resources\ReadsMembers;
use haddowg\JsonApiClient\Support\Identifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversClass(Pivot::class)]
#[CoversTrait(ReadsMembers::class)]
final class PivotTest extends TestCase
{
    public function testItReadsTheMembersPivotThroughTheTypedReaders(): void
    {
        $pivot = self::pivot(['position' => 2, 'weight' => null, 'addedAt' => '2026-01-02T03:04:05+00:00']);

        self::assertSame(2, $pivot->position());
        self::assertNull($pivot->weight());
        self::assertEquals(new \DateTimeImmutable('2026-01-02T03:04:05+00:00'), $pivot->addedAt());
    }

    public function testItKeepsTheMemberItBelongsTo(): void
    {
        $member = Identifier::of('tracks', '4', ['pivot' => ['position' => 1]]);
        $pivot = new OrderedTrackPivot($member);

        self::assertSame($member, $pivot->_identifier());
        self::assertSame(['position' => 1], $pivot->_raw());
    }

    public function testAnAbsentFieldReadsAsNullThroughANullableReader(): void
    {
        self::assertNull(self::pivot(['position' => 1])->weight());
    }

    public function testAnAbsentFieldIsMalformedThroughAStrictReader(): void
    {
        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('The pivot of the "tracks" "4" linkage member has a "position" member that is not an integer.');

        self::pivot([])->position();
    }

    public function testAMemberWithNoPivotReadsAsAnEmptyOne(): void
    {
        self::assertSame([], (new OrderedTrackPivot(Identifier::of('tracks', '4')))->_raw());
    }

    public function testAnUnparseableDateNamesThePivotItCameFrom(): void
    {
        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('The pivot of the "tracks" "4" linkage member has a "addedAt" member that is not a date/time this client can parse: yesterday-ish.');

        self::pivot(['addedAt' => 'yesterday-ish'])->addedAt();
    }

    /**
     * @param array<string, mixed> $fields
     */
    private static function pivot(array $fields): OrderedTrackPivot
    {
        return new OrderedTrackPivot(Identifier::of('tracks', '4', ['pivot' => $fields]));
    }
}

final class OrderedTrackPivot extends Pivot
{
    public function position(): int
    {
        return $this->_int('position');
    }

    public function weight(): ?int
    {
        return $this->_nullableInt('weight');
    }

    public function addedAt(): \DateTimeImmutable
    {
        return $this->_dateTime('addedAt');
    }
}
