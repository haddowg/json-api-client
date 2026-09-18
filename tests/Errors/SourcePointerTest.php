<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Errors;

use haddowg\JsonApiClient\Errors\SourcePointer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SourcePointer::class)]
final class SourcePointerTest extends TestCase
{
    #[DataProvider('writePointers')]
    public function testItInvertsAWritePointerToTheCallersInputPath(string $pointer, string $expected): void
    {
        self::assertSame($expected, SourcePointer::toPath($pointer));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function writePointers(): iterable
    {
        yield 'attribute' => ['/data/attributes/title', 'title'];
        yield 'nested object member' => ['/data/attributes/releaseInfo/label', 'releaseInfo.label'];
        yield 'deeply nested member' => ['/data/attributes/releaseInfo/address/city', 'releaseInfo.address.city'];
        yield 'client-generated id' => ['/data/id', 'id'];
        yield 'to-one relationship' => ['/data/relationships/artist/data', 'artist'];
        yield 'to-many member' => ['/data/relationships/tracks/data/0', 'tracks[0]'];
        yield 'pivot field' => [
            '/data/relationships/orderedTracks/data/0/meta/pivot/position',
            'orderedTracks[0]._pivot.position',
        ];
        yield 'to-one pivot field' => [
            '/data/relationships/owner/data/meta/pivot/role',
            'owner._pivot.role',
        ];
        yield 'linkage member meta that is not pivot' => [
            '/data/relationships/tracks/data/0/meta/addedAt',
            'tracks[0].meta.addedAt',
        ];
    }

    #[DataProvider('passThroughPointers')]
    public function testAPointerItCannotInvertIsReturnedVerbatim(string $pointer): void
    {
        // The raw pointer is always still on the error, so mangling one it does not recognise
        // would lose information for nothing.
        self::assertSame($pointer, SourcePointer::toPath($pointer));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function passThroughPointers(): iterable
    {
        yield 'the document root' => ['/data'];
        yield 'the type the client owns' => ['/data/type'];
        yield 'a bare attributes member' => ['/data/attributes'];
        yield 'an included resource' => ['/included/0/attributes/title'];
        yield 'something else entirely' => ['/meta/page'];
    }

    public function testARelationshipEndpointPointerResolvesUnderTheRoutesRelation(): void
    {
        // The linkage document carries no relation name — it comes from the URL.
        self::assertSame('tracks', SourcePointer::toRelationshipPath('tracks', '/data'));
        self::assertSame('tracks[0]', SourcePointer::toRelationshipPath('tracks', '/data/0'));
        self::assertSame(
            'orderedTracks[0]._pivot.position',
            SourcePointer::toRelationshipPath('orderedTracks', '/data/0/meta/pivot/position'),
        );
        self::assertSame(
            'owner._pivot.role',
            SourcePointer::toRelationshipPath('owner', '/data/meta/pivot/role'),
        );
    }

    public function testARelationshipProhibitionPointerCollapsesToTheRelationName(): void
    {
        // The server's relationship prohibitions point at the resource-document shape even on
        // a relationship endpoint, so both shapes have to land on the same path.
        self::assertSame(
            'tracks',
            SourcePointer::toRelationshipPath('tracks', '/data/relationships/tracks'),
        );
        self::assertSame(
            'tracks[1]',
            SourcePointer::toRelationshipPath('tracks', '/data/relationships/tracks/data/1'),
        );
    }

    public function testANonDataRelationshipPointerIsReturnedVerbatim(): void
    {
        self::assertSame('/meta/x', SourcePointer::toRelationshipPath('tracks', '/meta/x'));
    }

    public function testAnAtomicPointerSplitsIntoAnOperationIndexAndAPath(): void
    {
        self::assertSame(
            ['opIndex' => 2, 'path' => 'title'],
            SourcePointer::toAtomicPath('/atomic:operations/2/data/attributes/title'),
        );
        self::assertSame(
            ['opIndex' => 0, 'path' => 'releaseInfo.label'],
            SourcePointer::toAtomicPath('/atomic:operations/0/data/attributes/releaseInfo/label'),
        );
        self::assertSame(
            ['opIndex' => 1, 'path' => 'artist'],
            SourcePointer::toAtomicPath('/atomic:operations/1/data/relationships/artist/data'),
        );
    }

    public function testAPointerWithoutTheAtomicPrefixStillRemapsAtNoIndex(): void
    {
        self::assertSame(
            ['opIndex' => null, 'path' => 'title'],
            SourcePointer::toAtomicPath('/data/attributes/title'),
        );
    }

    public function testAnAtomicPrefixWithNoUsableIndexIsLeftAlone(): void
    {
        self::assertSame(
            ['opIndex' => null, 'path' => '/atomic:operations/x/data/attributes/title'],
            SourcePointer::toAtomicPath('/atomic:operations/x/data/attributes/title'),
        );
        self::assertSame(
            ['opIndex' => null, 'path' => '/atomic:operations'],
            SourcePointer::toAtomicPath('/atomic:operations'),
        );
    }

    public function testTheReservedPivotMemberCannotCollideWithAFieldName(): void
    {
        // JSON:API member names must start alphanumeric, so a leading underscore is private
        // to the client by specification.
        self::assertSame('_pivot', SourcePointer::PIVOT);
    }
}
