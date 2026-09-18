<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Support\Conditionable;
use haddowg\JsonApiClient\Support\Identifier;
use haddowg\JsonApiClient\Support\Missing;

/**
 * The fluent update input for `albums`.
 *
 * `build()` cannot fail here: `AlbumsUpdateAttributes` declares nothing required, so an empty
 * patch is a legal, if pointless, document. The builder is still the right door for a patch
 * assembled over branches, which is most of them.
 *
 * @generated from `components.schemas.AlbumsUpdateRequest`
 */
final class AlbumUpdateBuilder
{
    use Conditionable;

    private string|Missing $title = Missing::Value;

    private \DateTimeImmutable|Missing $releasedAt = Missing::Value;

    private bool|Missing $explicit = Missing::Value;

    private AlbumStatus|Missing $status = Missing::Value;

    private \DateTimeImmutable|Missing|null $availableFrom = Missing::Value;

    private \DateTimeImmutable|Missing|null $availableUntil = Missing::Value;

    /**
     * @var array{label?: string, catalogueNumber?: string}|Missing|null
     */
    private array|Missing|null $releaseInfo = Missing::Value;

    private Artist|Identifier|Missing|null $artist = Missing::Value;

    /**
     * @var list<Identifier|Track>|Missing
     */
    private array|Missing $tracks = Missing::Value;

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function releasedAt(\DateTimeImmutable $releasedAt): self
    {
        $this->releasedAt = $releasedAt;

        return $this;
    }

    public function explicit(bool $explicit = true): self
    {
        $this->explicit = $explicit;

        return $this;
    }

    public function status(AlbumStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function availableFrom(?\DateTimeImmutable $availableFrom): self
    {
        $this->availableFrom = $availableFrom;

        return $this;
    }

    public function availableUntil(?\DateTimeImmutable $availableUntil): self
    {
        $this->availableUntil = $availableUntil;

        return $this;
    }

    /**
     * @param array{label?: string, catalogueNumber?: string}|null $releaseInfo
     */
    public function releaseInfo(?array $releaseInfo): self
    {
        $this->releaseInfo = $releaseInfo;

        return $this;
    }

    public function artist(Artist|Identifier|null $artist): self
    {
        $this->artist = $artist;

        return $this;
    }

    /**
     * @param list<Identifier|Track> $tracks
     */
    public function tracks(array $tracks): self
    {
        $this->tracks = $tracks;

        return $this;
    }

    public function build(): AlbumUpdate
    {
        return new AlbumUpdate(
            title: $this->title,
            releasedAt: $this->releasedAt,
            explicit: $this->explicit,
            status: $this->status,
            availableFrom: $this->availableFrom,
            availableUntil: $this->availableUntil,
            releaseInfo: $this->releaseInfo,
            artist: $this->artist,
            tracks: $this->tracks,
        );
    }
}
