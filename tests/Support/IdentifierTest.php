<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Support;

use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Support\Identifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Identifier::class)]
#[UsesClass(MalformedDocument::class)]
final class IdentifierTest extends TestCase
{
    public function testItCarriesTheTypeIdPair(): void
    {
        $identifier = Identifier::of('albums', '1');

        self::assertSame('albums', $identifier->type);
        self::assertSame('1', $identifier->id);
        self::assertSame([], $identifier->meta);
        self::assertSame(['type' => 'albums', 'id' => '1'], $identifier->toArray());
    }

    public function testMetaIsOmittedFromTheWireFormWhenEmpty(): void
    {
        self::assertArrayNotHasKey('meta', Identifier::of('albums', '1')->toArray());
    }

    public function testMetaSurvivesTheRoundTrip(): void
    {
        $identifier = Identifier::fromArray([
            'type' => 'tracks',
            'id' => '4',
            'meta' => ['pivot' => ['position' => 1]],
        ]);

        self::assertSame(['pivot' => ['position' => 1]], $identifier->meta);
        self::assertSame(['position' => 1], $identifier->pivot());
        self::assertSame(
            ['type' => 'tracks', 'id' => '4', 'meta' => ['pivot' => ['position' => 1]]],
            $identifier->toArray(),
        );
    }

    public function testPivotIsEmptyWhenTheRelationCarriesNone(): void
    {
        self::assertSame([], Identifier::of('tracks', '4', ['addedAt' => 'yesterday'])->pivot());
    }

    public function testIdentityIsTheTypeIdPairAndNotTheMeta(): void
    {
        $one = Identifier::of('albums', '1', ['a' => 1]);

        self::assertTrue($one->is(Identifier::of('albums', '1')));
        self::assertFalse($one->is(Identifier::of('albums', '2')));
        self::assertFalse($one->is(Identifier::of('tracks', '1')));
    }

    public function testAMemberWithNoTypeIsRejected(): void
    {
        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('data[0] is missing a string "type" member.');

        Identifier::fromArray(['id' => '1'], 'data[0]');
    }

    public function testAMemberWithNoIdIsRejectedAndTheMessageNamesTheType(): void
    {
        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('data[0] (type "albums") is missing a string "id" member.');

        Identifier::fromArray(['type' => 'albums'], 'data[0]');
    }

    public function testAnIntegerIdIsRejectedRatherThanCoerced(): void
    {
        // JSON:API mandates string ids. Coercing would hide a server that broke the contract.
        $this->expectException(MalformedDocument::class);

        Identifier::fromArray(['type' => 'albums', 'id' => 1]);
    }

    public function testANonArrayMetaReadsAsEmpty(): void
    {
        self::assertSame([], Identifier::fromArray(['type' => 'albums', 'id' => '1', 'meta' => 'nope'])->meta);
    }
}
