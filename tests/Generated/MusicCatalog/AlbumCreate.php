<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Exceptions\UnknownAttributeException;
use haddowg\JsonApiClient\Support\Identifier;
use haddowg\JsonApiClient\Support\Missing;

/**
 * The canonical create input for `albums`, as named arguments.
 *
 * This is the form the docs lead with, because it is the only one of the three with no blind
 * spot: a missing required member is a static error *and* an `ArgumentCountError`, a typo is an
 * unknown named argument whether the member is required or optional, and every value type is
 * checked. The builder cannot statically require that a setter was called; the array shape
 * accepts any unknown *optional* key. Both are backstopped at runtime, and neither needs to be
 * when a DTO is used.
 *
 * `Missing` defaults every optional member because PHP has no way to say "this argument was not
 * passed", and JSON:API needs absent to stay distinct from an explicit `null` — an omitted
 * member leaves the server value alone, a `null` member clears it. The serialiser drops every
 * member still holding the sentinel.
 *
 * `averageRating` and `artwork` are absent here because they are absent from
 * `AlbumsCreateAttributes`: the server declares them read-only, so they are on the resource and
 * in no write shape.
 *
 * Required members take no default, so the declaration order is required-first. Named arguments
 * mean that never binds a caller.
 *
 * @phpstan-import-type AlbumCreateShape from AlbumShapes
 * @phpstan-import-type AlbumReleaseInfoShape from AlbumShapes
 *
 * @generated from `components.schemas.AlbumsCreateRequest`
 */
final class AlbumCreate
{
    /**
     * @param AlbumReleaseInfoShape|Missing|null $releaseInfo
     * @param Artist|Identifier|Missing|null     $artist
     * @param list<Identifier|Track>|Missing     $tracks
     */
    public function __construct(
        public readonly string $title,
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
     * Build the DTO from the array door, checking every key against the descriptor.
     *
     * The check is the point. An all-optional array shape accepts any unknown key, so
     * `['titel' => 'x']` type-checks, sends nothing and silently no-ops — the only way to lose
     * data through this API, and the reason this method exists rather than the array being
     * passed straight through.
     *
     * @param AlbumCreateShape $input
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
        // sentinel exists for.
        return new self(
            title: $input['title'],
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
     * The `POST /albums` request document.
     *
     * One serialisation path for all three input forms: the builder's `build()` produces this
     * DTO and the array goes through `from()`, so nothing else ever writes a document.
     *
     * @return array<string, mixed>
     */
    public function toDocument(): array
    {
        $data = ['type' => AlbumBase::TYPE];

        // Each member's wire format is known at generation time, which is where that knowledge
        // belongs: `releasedAt` is `format: date-time` and `availableFrom` is `format: date`,
        // and the runtime never has to guess between them.
        $attributes = ['title' => $this->title];

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

        $data['attributes'] = $attributes;

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
