<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Exceptions\UnknownAttributeException;
use haddowg\JsonApiClient\Support\Identifier;
use haddowg\JsonApiClient\Support\Missing;

/**
 * The canonical update input for `albums`: every member optional, which is what a JSON:API
 * `PATCH` is.
 *
 * `AlbumsUpdateAttributes` declares no `required` list, so nothing here takes a value and the
 * whole shape sits in the array door's one blind spot — a typo on an optional key compiles,
 * sends nothing, and looks like success. `from()` is the guard that closes it, and on a
 * type whose write shape is entirely optional it is not a nicety.
 *
 * The id is not a member: it comes from the handle the update is called on
 * (`$client->albums->id('1')->update(…)`), so it cannot disagree with the URL.
 *
 * @phpstan-import-type AlbumReleaseInfoShape from AlbumShapes
 * @phpstan-import-type AlbumUpdateShape from AlbumShapes
 *
 * @generated from `components.schemas.AlbumsUpdateRequest`
 */
final class AlbumUpdate
{
    /**
     * @param AlbumReleaseInfoShape|Missing|null $releaseInfo
     * @param Artist|Identifier|Missing|null     $artist
     * @param list<Identifier|Track>|Missing     $tracks
     */
    public function __construct(
        public readonly string|Missing $title = Missing::Value,
        public readonly \DateTimeImmutable|Missing $releasedAt = Missing::Value,
        public readonly bool|Missing $explicit = Missing::Value,
        public readonly AlbumStatus|Missing $status = Missing::Value,
        public readonly \DateTimeImmutable|Missing|null $availableFrom = Missing::Value,
        public readonly \DateTimeImmutable|Missing|null $availableUntil = Missing::Value,
        public readonly array|Missing|null $releaseInfo = Missing::Value,
        public readonly Artist|Identifier|Missing|null $artist = Missing::Value,
        public readonly array|Missing $tracks = Missing::Value,
    ) {}

    /**
     * @param AlbumUpdateShape $input
     *
     * @throws UnknownAttributeException when a key is not a writable member of `albums`
     */
    public static function from(array $input): self
    {
        foreach (\array_keys($input) as $member) {
            if (!\in_array($member, AlbumShapes::WRITABLE, true)) {
                throw UnknownAttributeException::for(AlbumBase::TYPE, (string) $member, AlbumShapes::WRITABLE);
            }
        }

        // `array_key_exists` and not `??`: a member holding an explicit `null` is present and
        // clears the server value, and `??` would fold it into the sentinel that means absent —
        // silently turning "clear this" into "leave it alone", which is the one distinction the
        // sentinel exists for. On an all-optional patch shape this is the difference between a
        // working `PATCH` and a no-op.
        return new self(
            title: \array_key_exists('title', $input) ? $input['title'] : Missing::Value,
            releasedAt: AlbumWrite::dateTime(\array_key_exists('releasedAt', $input) ? $input['releasedAt'] : Missing::Value),
            explicit: \array_key_exists('explicit', $input) ? $input['explicit'] : Missing::Value,
            status: AlbumWrite::status(\array_key_exists('status', $input) ? $input['status'] : Missing::Value),
            availableFrom: AlbumWrite::nullableDate(\array_key_exists('availableFrom', $input) ? $input['availableFrom'] : Missing::Value),
            availableUntil: AlbumWrite::nullableDate(\array_key_exists('availableUntil', $input) ? $input['availableUntil'] : Missing::Value),
            releaseInfo: \array_key_exists('releaseInfo', $input) ? $input['releaseInfo'] : Missing::Value,
            artist: \array_key_exists('artist', $input) ? $input['artist'] : Missing::Value,
            tracks: \array_key_exists('tracks', $input) ? $input['tracks'] : Missing::Value,
        );
    }

    /**
     * The `PATCH /albums/{id}` request document.
     *
     * Only members that were set reach the wire. A member holding `null` is sent as `null` and
     * clears the server value; a member holding {@see Missing} is not sent at all and leaves it
     * alone. That distinction is the whole reason the sentinel exists.
     *
     * @return array<string, mixed>
     */
    public function toDocument(string $id): array
    {
        $data = ['type' => AlbumBase::TYPE, 'id' => $id];
        $attributes = [];

        if (!$this->title instanceof Missing) {
            $attributes['title'] = $this->title;
        }

        if (!$this->releasedAt instanceof Missing) {
            $attributes['releasedAt'] = $this->releasedAt->format(\DateTimeInterface::ATOM);
        }

        if (!$this->explicit instanceof Missing) {
            $attributes['explicit'] = $this->explicit;
        }

        if (!$this->status instanceof Missing) {
            $attributes['status'] = $this->status->value;
        }

        if (!$this->availableFrom instanceof Missing) {
            $attributes['availableFrom'] = $this->availableFrom?->format('Y-m-d');
        }

        if (!$this->availableUntil instanceof Missing) {
            $attributes['availableUntil'] = $this->availableUntil?->format('Y-m-d');
        }

        if (!$this->releaseInfo instanceof Missing) {
            $attributes['releaseInfo'] = $this->releaseInfo;
        }

        if ($attributes !== []) {
            $data['attributes'] = $attributes;
        }

        $relationships = [];

        if (!$this->artist instanceof Missing) {
            $relationships['artist'] = ['data' => AlbumWrite::linkage($this->artist)];
        }

        if (!$this->tracks instanceof Missing) {
            $relationships['tracks'] = ['data' => AlbumWrite::linkageList($this->tracks)];
        }

        if ($relationships !== []) {
            $data['relationships'] = $relationships;
        }

        return ['data' => $data];
    }
}
