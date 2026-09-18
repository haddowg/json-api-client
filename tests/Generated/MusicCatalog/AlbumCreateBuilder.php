<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Exceptions\MissingRequiredMemberException;
use haddowg\JsonApiClient\Support\Conditionable;
use haddowg\JsonApiClient\Support\Identifier;
use haddowg\JsonApiClient\Support\Missing;

/**
 * The fluent create input for `albums`, for assembly that is conditional.
 *
 * Its reason to exist is `when()`/`unless()`/`tap()`: building a write over branches without
 * an intermediate array, with the callback parameter typed so a typo'd setter and a wrong value
 * type inside the closure are both caught. Where nothing is conditional the DTO is strictly
 * better, and `build()` is what backstops the one thing this form cannot check statically.
 *
 * Mutable and self-returning, unlike the immutable query builders. Write builders narrow
 * nothing, so there is no projection to lose and the callback's return can simply be honoured.
 *
 * @generated from `components.schemas.AlbumsCreateRequest`
 */
final class AlbumCreateBuilder
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

    /**
     * A relation setter takes the resource itself or a bare reference, so a value in hand and
     * an id you happen to know are the same call.
     */
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

    /**
     * Produce the DTO, which is the only form that ever reaches the serialiser.
     *
     * @throws MissingRequiredMemberException when a member the create document requires was
     *                                        never set — the builder's one blind spot, which no
     *                                        amount of typing can close
     */
    public function build(): AlbumCreate
    {
        // One guard per required member rather than a collected list: narrowing has to survive
        // into the constructor call, and PHPStan cannot connect `$missing !== []` back to the
        // property it was collected from.
        if ($this->title instanceof Missing) {
            throw MissingRequiredMemberException::for(AlbumBase::TYPE, ['title']);
        }

        return new AlbumCreate(
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
