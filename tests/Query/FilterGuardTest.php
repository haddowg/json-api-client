<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Query;

use haddowg\JsonApiClient\Exceptions\UnknownFilterException;
use haddowg\JsonApiClient\Query\FilterGuard;
use haddowg\JsonApiClient\Query\ReadQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FilterGuard::class)]
final class FilterGuardTest extends TestCase
{
    private const array NAMES = ['status', 'rating', 'labels'];

    private const array MEMBERS = ['rating' => ['min', 'max']];

    #[Test]
    public function itReturnsAFilterOfAcceptedNamesUnchanged(): void
    {
        $filter = ['status' => 'draft', 'rating' => ['min' => 2], 'labels' => ['a', 'b']];

        self::assertSame($filter, FilterGuard::check($filter, self::NAMES, self::MEMBERS));
        self::assertSame(
            '/articles/-actions/bulk-archive?filter[status]=draft&filter[rating][min]=2&filter[labels]=a%2Cb',
            (new ReadQuery(filter: $filter))->appendTo('/articles/-actions/bulk-archive'),
        );
    }

    #[Test]
    public function itRefusesANameTheEndpointDoesNotAccept(): void
    {
        $this->expectException(UnknownFilterException::class);
        $this->expectExceptionMessage('"stats" is not a filter this endpoint accepts. Did you mean "status"?');

        FilterGuard::check(['stats' => 'draft'], self::NAMES, self::MEMBERS);
    }

    #[Test]
    public function itRefusesAMemberAStructuredFilterDoesNotAccept(): void
    {
        try {
            FilterGuard::check(['rating' => ['mni' => 2]], self::NAMES, self::MEMBERS);
            self::fail('The unknown member was accepted.');
        } catch (UnknownFilterException $e) {
            self::assertSame('rating[mni]', $e->filter);
            self::assertStringContainsString('Did you mean "rating[min]"?', $e->getMessage());
        }
    }

    #[Test]
    public function itLeavesAListValueAndAnUnstructuredFilterAlone(): void
    {
        self::assertSame(['labels' => ['x']], FilterGuard::check(['labels' => ['x']], self::NAMES));
    }
}
