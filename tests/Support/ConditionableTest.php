<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Support;

use haddowg\JsonApiClient\Support\Conditionable;
use haddowg\JsonApiClient\Support\Missing;
use haddowg\JsonApiClient\Tests\Fixtures\AlbumCreateBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Conditionable::class)]
#[UsesClass(AlbumCreateBuilder::class)]
final class ConditionableTest extends TestCase
{
    public function testWhenRunsTheCallbackOnlyForATruthyCondition(): void
    {
        $applied = (new AlbumCreateBuilder())->when(true, static fn(AlbumCreateBuilder $b): AlbumCreateBuilder => $b->title('Geogaddi'));
        $skipped = (new AlbumCreateBuilder())->when(false, static fn(AlbumCreateBuilder $b): AlbumCreateBuilder => $b->title('Geogaddi'));

        self::assertSame('Geogaddi', $applied->title);
        self::assertSame(Missing::Value, $skipped->title);
    }

    public function testWhenFallsBackToTheDefaultCallback(): void
    {
        $builder = (new AlbumCreateBuilder())->when(
            false,
            static fn(AlbumCreateBuilder $b): AlbumCreateBuilder => $b->status('published'),
            static fn(AlbumCreateBuilder $b): AlbumCreateBuilder => $b->status('draft'),
        );

        self::assertSame('draft', $builder->status);
    }

    public function testWhenHonoursACallbackThatReturnsAReplacement(): void
    {
        $replacement = (new AlbumCreateBuilder())->title('Music Has The Right To Children');

        $result = (new AlbumCreateBuilder())->when(
            true,
            static fn(): AlbumCreateBuilder => $replacement,
        );

        self::assertSame($replacement, $result);
    }

    public function testWhenKeepsTheReceiverWhenTheCallbackReturnsNull(): void
    {
        $builder = new AlbumCreateBuilder();

        $result = $builder->when(true, static function (AlbumCreateBuilder $b): null {
            $b->title('Geogaddi');

            return null;
        });

        self::assertSame($builder, $result);
        self::assertSame('Geogaddi', $result->title);
    }

    public function testAClosureConditionIsResolvedAgainstTheBuilder(): void
    {
        $builder = (new AlbumCreateBuilder())->title('Geogaddi');

        $result = $builder->when(
            static fn(AlbumCreateBuilder $b): bool => $b->title === 'Geogaddi',
            static fn(AlbumCreateBuilder $b): AlbumCreateBuilder => $b->status('published'),
        );

        self::assertSame('published', $result->status);
    }

    public function testACallableStringIsAValueAndIsNeverInvoked(): void
    {
        $result = (new AlbumCreateBuilder())->when(
            'strlen',
            static fn(AlbumCreateBuilder $b): AlbumCreateBuilder => $b->status('published'),
        );

        // A non-empty string is truthy, so the callback runs — but `strlen` was never called.
        self::assertSame('published', $result->status);
    }

    public function testUnlessIsTheInverseOfWhen(): void
    {
        $applied = (new AlbumCreateBuilder())->unless(false, static fn(AlbumCreateBuilder $b): AlbumCreateBuilder => $b->title('Geogaddi'));
        $skipped = (new AlbumCreateBuilder())->unless(true, static fn(AlbumCreateBuilder $b): AlbumCreateBuilder => $b->title('Geogaddi'));

        self::assertSame('Geogaddi', $applied->title);
        self::assertSame(Missing::Value, $skipped->title);
    }

    public function testUnlessFallsBackToTheDefaultCallback(): void
    {
        $builder = (new AlbumCreateBuilder())->unless(
            true,
            static fn(AlbumCreateBuilder $b): AlbumCreateBuilder => $b->status('published'),
            static fn(AlbumCreateBuilder $b): AlbumCreateBuilder => $b->status('draft'),
        );

        self::assertSame('draft', $builder->status);
    }

    public function testTapDiscardsWhateverTheCallbackReturns(): void
    {
        $builder = new AlbumCreateBuilder();
        $other = new AlbumCreateBuilder();
        $seen = null;

        $result = $builder->tap(static function (AlbumCreateBuilder $b) use ($other, &$seen): AlbumCreateBuilder {
            $seen = $b;

            return $other;
        });

        self::assertSame($builder, $result);
        self::assertSame($builder, $seen);
    }

    public function testConditionsCompose(): void
    {
        $builder = (new AlbumCreateBuilder())
            ->title('Geogaddi')
            ->when(true, static fn(AlbumCreateBuilder $b): AlbumCreateBuilder => $b->status('published'))
            ->unless(true, static fn(AlbumCreateBuilder $b): AlbumCreateBuilder => $b->title('overwritten'));

        self::assertSame(['title', 'status'], $builder->applied);
    }
}
