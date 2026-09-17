<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests;

use haddowg\JsonApiClient\MediaType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MediaType::class)]
final class MediaTypeTest extends TestCase
{
    public function testTheBareMediaTypeCarriesNoParameters(): void
    {
        self::assertSame('application/vnd.api+json', MediaType::value());
    }

    public function testExtensionsAreEmittedAsOneSpaceSeparatedParameter(): void
    {
        self::assertSame(
            'application/vnd.api+json; ext="https://jsonapi.org/ext/atomic https://example.com/ext/version"',
            MediaType::value([MediaType::ATOMIC_OPERATIONS, 'https://example.com/ext/version']),
        );
    }

    public function testProfilesFollowExtensions(): void
    {
        self::assertSame(
            'application/vnd.api+json; ext="https://jsonapi.org/ext/atomic"; profile="https://example.com/cursor"',
            MediaType::value([MediaType::ATOMIC_OPERATIONS], ['https://example.com/cursor']),
        );
    }

    public function testProfilesAloneOmitTheExtParameter(): void
    {
        self::assertSame(
            'application/vnd.api+json; profile="https://example.com/cursor"',
            MediaType::value(profiles: ['https://example.com/cursor']),
        );
    }
}
