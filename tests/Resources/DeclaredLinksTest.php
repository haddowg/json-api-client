<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Resources;

use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Resources\DeclaredLink;
use haddowg\JsonApiClient\Resources\DeclaredLinks;
use haddowg\JsonApiClient\Resources\LinkMeta;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeclaredLinks::class)]
#[CoversClass(DeclaredLink::class)]
#[CoversClass(LinkMeta::class)]
final class DeclaredLinksTest extends TestCase
{
    public function testALinkObjectReadsItsHrefStaticMembersAndTypedMeta(): void
    {
        $download = self::links()->download();

        self::assertNotNull($download);
        self::assertSame('https://cdn.example.com/img-1.png', $download->href());
        self::assertSame('Original', $download->title());
        self::assertSame('GET', $download->meta()->method());
        self::assertEquals(new \DateTimeImmutable('2026-10-26T12:00:00+00:00'), $download->meta()->expiresAt());
        self::assertSame(['Range' => 'bytes=0-'], $download->meta()->headers());
    }

    public function testABareLinkReadsItsUrlFromAStringOrALinkObject(): void
    {
        self::assertSame('https://cdn.example.com/thumb.png', self::links()->thumbnail());
        self::assertSame('https://cdn.example.com/thumb.png', self::links(['thumbnail' => ['href' => 'https://cdn.example.com/thumb.png']])->thumbnail());
    }

    public function testAnAbsentLinkReadsAsNull(): void
    {
        $links = new ImageLinks([], 'The "images" resource');

        self::assertNull($links->download());
        self::assertNull($links->thumbnail());
        self::assertSame([], $links->_raw());
    }

    public function testAMetaMemberTheServerCanLeaveOutReadsAsNull(): void
    {
        $download = self::links(['download' => ['href' => '/d', 'title' => 'Original', 'meta' => ['method' => 'GET', 'expiresAt' => '2026-10-26T12:00:00+00:00']]])->download();

        self::assertNotNull($download);
        self::assertNull($download->meta()->headers());
    }

    public function testALinkWithNoMetaGetsAnEmptyOne(): void
    {
        $download = self::links(['download' => ['href' => '/d']])->download();

        self::assertNotNull($download);
        self::assertSame([], $download->meta()->_raw());
        self::assertNull($download->meta()->headers());
    }

    public function testAMissingRequiredMetaMemberNamesTheLinkItBelongsTo(): void
    {
        $download = self::links(['download' => ['href' => '/d', 'meta' => []]])->download();
        self::assertNotNull($download);

        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('The meta of the "download" link of the "images" resource has a "method" member that is not a string.');

        $download->meta()->method();
    }

    public function testALinkObjectWithNoHrefIsMalformed(): void
    {
        $download = self::links(['download' => ['title' => 'Original']])->download();
        self::assertNotNull($download);

        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('The "download" link of the "images" resource has a "href" member that is not a string.');

        $download->href();
    }

    public function testALinkObjectRenderedAsAStringIsMalformed(): void
    {
        $this->expectException(MalformedDocument::class);
        $this->expectExceptionMessage('The links of the "images" resource has a "download" member that is not an object or null.');

        self::links(['download' => 'https://cdn.example.com/img-1.png'])->download();
    }

    public function testTheLinkKeepsWhatArrived(): void
    {
        $download = self::links(['download' => ['href' => '/d', 'meta' => ['method' => 'GET']]])->download();

        self::assertNotNull($download);
        self::assertSame(['href' => '/d', 'meta' => ['method' => 'GET']], $download->_raw());
        self::assertSame(['method' => 'GET'], $download->meta()->_raw());
    }

    /**
     * @param array<string, mixed>|null $links
     */
    private static function links(?array $links = null): ImageLinks
    {
        return new ImageLinks($links ?? [
            'self' => 'https://api.example.com/images/img-1',
            'download' => [
                'href' => 'https://cdn.example.com/img-1.png',
                'title' => 'Original',
                'meta' => ['method' => 'GET', 'expiresAt' => '2026-10-26T12:00:00+00:00', 'headers' => ['Range' => 'bytes=0-']],
            ],
            'thumbnail' => 'https://cdn.example.com/thumb.png',
        ], 'The "images" resource');
    }
}

final class ImageLinks extends DeclaredLinks
{
    public function download(): ?ImageDownloadLink
    {
        return $this->_object('download', ImageDownloadLink::class);
    }

    public function thumbnail(): ?string
    {
        return $this->_href('thumbnail');
    }
}

final class ImageDownloadLink extends DeclaredLink
{
    public function title(): string
    {
        return $this->_string('title');
    }

    public function meta(): ImageDownloadLinkMeta
    {
        return $this->_metaAs(ImageDownloadLinkMeta::class);
    }
}

final class ImageDownloadLinkMeta extends LinkMeta
{
    public function method(): string
    {
        return $this->_string('method');
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->_dateTime('expiresAt');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function headers(): ?array
    {
        return $this->_nullableMap('headers');
    }
}
