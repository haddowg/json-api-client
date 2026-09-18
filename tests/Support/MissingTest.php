<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Support;

use haddowg\JsonApiClient\Support\Missing;
use haddowg\JsonApiClient\Tests\Fixtures\AlbumCreateBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Missing::class)]
#[UsesClass(AlbumCreateBuilder::class)]
final class MissingTest extends TestCase
{
    public function testItIsASingleCaseSentinelSoIdentityIsEnough(): void
    {
        self::assertCount(1, Missing::cases());
        self::assertSame(Missing::Value, Missing::cases()[0]);
    }

    public function testAnAbsentMemberIsDistinctFromAnExplicitNull(): void
    {
        // The distinction a PATCH turns on: absent leaves the server value alone, null clears
        // it. So the serialiser's filter has to keep a null and drop only the sentinel.
        $members = ['title' => Missing::Value, 'releasedAt' => null, 'status' => 'published'];

        $sent = \array_filter($members, static fn(mixed $value): bool => !$value instanceof Missing);

        self::assertSame(['releasedAt' => null, 'status' => 'published'], $sent);
    }

    public function testItDefaultsAWriteBuilderMemberUntilOneIsSet(): void
    {
        $builder = new AlbumCreateBuilder();

        self::assertSame(Missing::Value, $builder->title);

        $builder->title('Geogaddi');

        self::assertSame('Geogaddi', $builder->title);
    }
}
