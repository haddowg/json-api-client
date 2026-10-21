<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Support;

use haddowg\JsonApiClient\Support\ImmutableConditionable;
use haddowg\JsonApiClient\Support\Missing;
use haddowg\JsonApiClient\Tests\Fixtures\AlbumUpdate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImmutableConditionable::class)]
#[UsesClass(AlbumUpdate::class)]
final class ImmutableConditionableTest extends TestCase
{
    public function testWhenReturnsWhatTheCallbackBuiltOnlyForATruthyCondition(): void
    {
        $original = new AlbumUpdate();

        $applied = $original->when(true, static fn(AlbumUpdate $u): AlbumUpdate => $u->title('Geogaddi'));
        $skipped = $original->when(false, static fn(AlbumUpdate $u): AlbumUpdate => $u->title('Geogaddi'));

        self::assertSame('Geogaddi', $applied->title);
        self::assertSame($original, $skipped);
        self::assertSame(Missing::Value, $original->title, 'the receiver is never mutated');
    }

    public function testWhenFallsBackToTheDefaultCallback(): void
    {
        $update = (new AlbumUpdate())->when(
            false,
            static fn(AlbumUpdate $u): AlbumUpdate => $u->status('published'),
            static fn(AlbumUpdate $u): AlbumUpdate => $u->status('draft'),
        );

        self::assertSame('draft', $update->status);
    }

    public function testAClosureConditionIsResolvedAgainstTheInput(): void
    {
        $titled = (new AlbumUpdate())->title('Geogaddi');

        $update = $titled->when(
            static fn(AlbumUpdate $u): bool => $u->title !== Missing::Value,
            static fn(AlbumUpdate $u): AlbumUpdate => $u->status('published'),
        );

        self::assertSame('published', $update->status);
    }

    public function testUnlessRunsTheCallbackForAFalsyCondition(): void
    {
        $update = (new AlbumUpdate())->unless(false, static fn(AlbumUpdate $u): AlbumUpdate => $u->title('Geogaddi'));

        self::assertSame('Geogaddi', $update->title);
    }

    public function testTapReturnsTheInputWhateverTheCallbackReturns(): void
    {
        $original = new AlbumUpdate();
        $seen = [];

        $tapped = $original->tap(static function (AlbumUpdate $u) use (&$seen): AlbumUpdate {
            $seen[] = $u;

            return $u->title('ignored');
        });

        self::assertSame($original, $tapped);
        self::assertSame([$original], $seen);
    }
}
