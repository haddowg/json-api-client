<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Support;

use haddowg\JsonApiClient\Support\Wire;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Wire::class)]
final class WireTest extends TestCase
{
    public function testAMemberOfTheWrongTypeReadsAsAbsent(): void
    {
        // Light structural guards, not validation: a bad member is dropped, never thrown on.
        self::assertNull(Wire::string(['version' => 1.1], 'version'));
        self::assertNull(Wire::int(['total' => 'lots'], 'total'));
        self::assertSame([], Wire::map(['meta' => 'nope'], 'meta'));
        self::assertSame([], Wire::strings(['ext' => 'nope'], 'ext'));
    }

    public function testIntAcceptsTheNumericStringAJsonSourceMayCarry(): void
    {
        self::assertSame(42, Wire::int(['total' => 42], 'total'));
        self::assertSame(42, Wire::int(['total' => '42'], 'total'));
        self::assertSame(-1, Wire::int(['total' => '-1'], 'total'));
        self::assertNull(Wire::int(['total' => '42.5'], 'total'));
        self::assertNull(Wire::int(['total' => true], 'total'));
    }

    public function testMapKeepsOnlyObjectMembers(): void
    {
        self::assertSame(['page' => ['total' => 3]], Wire::map(['meta' => ['page' => ['total' => 3]]], 'meta'));
        self::assertSame(['0' => 'a', '1' => 'b'], Wire::map(['meta' => ['a', 'b']], 'meta'));
    }

    public function testStringsSkipsMembersThatAreNotStrings(): void
    {
        self::assertSame(
            ['https://jsonapi.org/ext/atomic'],
            Wire::strings(['ext' => ['https://jsonapi.org/ext/atomic', 7, null]], 'ext'),
        );
    }

    #[DataProvider('links')]
    public function testHrefResolvesBothLinkForms(mixed $link, ?string $expected): void
    {
        self::assertSame($expected, Wire::href($link));
    }

    /**
     * @return iterable<string, array{mixed, string|null}>
     */
    public static function links(): iterable
    {
        yield 'string form' => ['/albums?page[number]=2', '/albums?page[number]=2'];
        yield 'object form' => [['href' => '/albums?page[number]=2'], '/albums?page[number]=2'];
        yield 'object form with meta' => [['href' => '/albums', 'meta' => ['count' => 3]], '/albums'];
        yield 'object with no href' => [['meta' => []], null];
        yield 'absent' => [null, null];
        yield 'wrong type' => [42, null];
    }

    public function testDecodeReturnsNullForAnythingThatIsNotAJsonObject(): void
    {
        self::assertNull(Wire::decode(''));
        self::assertNull(Wire::decode('   '));
        self::assertNull(Wire::decode('<html>502 Bad Gateway</html>'));
        self::assertNull(Wire::decode('"a string"'));
        self::assertNull(Wire::decode('null'));
    }

    public function testDecodeReadsAJsonObject(): void
    {
        self::assertSame(['data' => ['type' => 'albums']], Wire::decode('{"data":{"type":"albums"}}'));
    }

    public function testDecodeAcceptsAJsonArrayAsAKeyedMap(): void
    {
        self::assertSame(['0' => 1, '1' => 2], Wire::decode('[1,2]'));
    }
}
