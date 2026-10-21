<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Collections\ResourceCollection;
use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Exceptions\RelationNotIncludedException;
use haddowg\JsonApiClient\Resources\ResourceContext;
use haddowg\JsonApiClient\Support\Identifier;

/**
 * An `albums` resource as it actually arrives: every attribute, and every relation accessor.
 *
 * The runtime value is always this class — the projection is a *static* narrowing over it, so
 * `$album->tracks` exists at runtime whether or not it type-checks, and calling it without the
 * include throws. That asymmetry is the design: static checking where it is cheap, a loud
 * runtime backstop everywhere else.
 *
 * The write entry points are static factories here rather than on the client accessor. Both
 * arities return an inert value, so neither performs IO and a forgotten `create()` call cannot
 * silently no-op — and one import reaches every write form for the type.
 *
 * It also carries every array shape the `albums` surface accepts, and the descriptor constants
 * the runtime guards check against. `@phpstan-type` aliases are class-local, so they have to be
 * declared on some class and imported everywhere else they are used. That import is not
 * optional bookkeeping: without a matching `@phpstan-import-type` the alias silently degrades to
 * bare `array`, nothing is reported, and every array-shape check on that door is lost.
 *
 * @phpstan-type AlbumReleaseInfoShape array{label?: string, catalogueNumber?: string}
 * @phpstan-type AlbumStatusInput AlbumStatus|'released'|'upcoming'|'withdrawn'
 * @phpstan-type AlbumDateInput \DateTimeImmutable|string
 * @phpstan-type AlbumArtistInput Artist|Identifier|null
 * @phpstan-type AlbumTracksInput list<Identifier|Track>
 * @phpstan-type AlbumCreateShape array{title: string, releasedAt?: AlbumDateInput, explicit?: bool, status?: AlbumStatusInput, availableFrom?: AlbumDateInput|null, availableUntil?: AlbumDateInput|null, releaseInfo?: AlbumReleaseInfoShape|null, artist?: AlbumArtistInput, tracks?: AlbumTracksInput}
 * @phpstan-type AlbumUpdateShape array{title?: string, releasedAt?: AlbumDateInput, explicit?: bool, status?: AlbumStatusInput, availableFrom?: AlbumDateInput|null, availableUntil?: AlbumDateInput|null, releaseInfo?: AlbumReleaseInfoShape|null, artist?: AlbumArtistInput, tracks?: AlbumTracksInput}
 * @phpstan-type AlbumRangeShape array{min?: float, max?: float}
 * @phpstan-type AlbumDateRangeShape array{min?: AlbumDateInput, max?: AlbumDateInput}
 * @phpstan-type AlbumFilterShape array{tracks?: string, 'artist.name'?: string, title?: string, rating?: AlbumRangeShape, releasedAt?: AlbumDateRangeShape, q?: string}
 * @phpstan-type AlbumSortToken '-releasedAt'|'-status'|'-title'|'releasedAt'|'status'|'title'
 * @phpstan-type AlbumIncludePath 'artist'|'artist.albums'|'tracks'|'tracks.album'|'tracks.playlists'
 * @phpstan-type AlbumFieldToken 'artist'|'artwork'|'availableFrom'|'availableUntil'|'averageRating'|'explicit'|'releaseInfo'|'releasedAt'|'status'|'title'|'tracks'
 * @phpstan-type AlbumCountToken 'tracks'
 *
 * @generated from `components.schemas.AlbumsResource`
 */
final class Album extends AlbumBase implements AlbumHasArtist, AlbumHasTracks
{
    /**
     * Every member a write input accepts, in document order. The runtime guard on the array
     * door checks against this, and the did-you-mean is drawn from it.
     *
     * @var list<string>
     */
    public const array WRITABLE = [
        'title',
        'releasedAt',
        'explicit',
        'status',
        'availableFrom',
        'availableUntil',
        'releaseInfo',
        'artist',
        'tracks',
    ];

    /**
     * Members a create document requires. `averageRating` and `artwork` are absent from
     * `AlbumsCreateAttributes` entirely: they are read-only, so they appear on the resource and
     * in no write shape.
     *
     * @var list<string>
     */
    public const array REQUIRED_ON_CREATE = ['title'];

    /**
     * @var list<string>
     */
    public const array FILTERS = ['tracks', 'artist.name', 'title', 'rating', 'releasedAt', 'q'];

    /**
     * The members a structured filter's own value accepts.
     *
     * The top-level guard does not reach these: an array shape rejects a wrong *value* type
     * inside a nested map but accepts an unknown key there, exactly as it does at the top level,
     * and `filter(['rating' => ['minimum' => 4.0]])` would otherwise compile and filter nothing.
     *
     * @var array<string, list<string>>
     */
    public const array FILTER_MEMBERS = [
        'rating' => ['min', 'max'],
        'releasedAt' => ['min', 'max'],
    ];

    /**
     * @var list<string>
     */
    public const array SORT_TOKENS = ['title', '-title', 'releasedAt', '-releasedAt', 'status', '-status'];

    /**
     * @var list<string>
     */
    public const array FIELDS = [
        'title',
        'averageRating',
        'artwork',
        'releasedAt',
        'explicit',
        'status',
        'availableFrom',
        'availableUntil',
        'releaseInfo',
        'artist',
        'tracks',
    ];

    /**
     * The `withCount` tokens `GET /albums` advertises.
     *
     * The collection endpoint lists `tracks` and not `_self_`, even though its own parameter
     * description says `_self_` counts the collection. Generated code follows the enum, so
     * `_self_` is not offered here and `_page()->total` stays null on this endpoint.
     *
     * @var list<string>
     */
    public const array COUNT_TOKENS = ['tracks'];

    /**
     * Requesting a `withCount` token opts into the Countable profile, which the client adds to
     * `Accept` only when a request actually uses a profile-gated parameter.
     */
    public const string COUNTABLE_PROFILE = 'https://haddowg.github.io/json-api/profiles/countable/';

    public ?Artist $artist { get => $this->artist(); }

    /** @var ResourceCollection<Track> */
    public ResourceCollection $tracks { get => $this->tracks(); }

    /**
     * Build a resource from one resource object of an indexed response.
     *
     * @param array<string, mixed> $raw
     *
     * @throws MalformedDocument when the resource object is not an `albums` one
     */
    public static function _hydrate(array $raw, ResourceContext $context): self
    {
        $identifier = Identifier::fromArray($raw, 'an "' . self::TYPE . '" resource object');

        if ($identifier->type !== self::TYPE) {
            throw MalformedDocument::unexpectedMember(
                'The resource object',
                'type',
                '"' . self::TYPE . '" (got "' . $identifier->type . '")',
            );
        }

        return new self($identifier, $raw, $context);
    }

    /**
     * A resource identifier for an album that is not in hand, for a relationship write.
     *
     * @param array<string, mixed> $meta
     */
    public static function ref(string $id, array $meta = []): Identifier
    {
        return Identifier::of(self::TYPE, $id, $meta);
    }

    /**
     * A reusable projection for a write response: `include` and `fields`, and nothing a `POST`
     * would be rejected for carrying.
     *
     * @return AlbumProjection<AlbumBase>
     */
    public static function projection(): AlbumProjection
    {
        return AlbumProjection::make();
    }

    /**
     * The create input, as a builder or as the canonical DTO.
     *
     * Both arities are expressible in one signature. Passing nothing hands back a builder for
     * conditional assembly; passing an array validates its keys against the descriptor and
     * hands back the DTO the builder would have produced.
     *
     * @param AlbumCreateShape|null $input
     *
     * @return ($input is null ? AlbumCreateBuilder : AlbumCreate)
     *
     * @throws \haddowg\JsonApiClient\Exceptions\UnknownAttributeException when the array names a member `albums` does not have
     */
    public static function create(?array $input = null): AlbumCreateBuilder|AlbumCreate
    {
        return $input === null ? new AlbumCreateBuilder() : AlbumCreate::from($input);
    }

    /**
     * The update input: an empty one to assemble fluently, or one built from an array.
     *
     * Every update member is optional, so the fluent form needs no separate builder. Each setter
     * returns a new input, which is why a discarded setter call is a static error rather than a
     * silent no-op.
     *
     * @param AlbumUpdateShape|null $input
     *
     * @throws \haddowg\JsonApiClient\Exceptions\UnknownAttributeException when the array names a member `albums` does not have
     */
    public static function update(?array $input = null): AlbumUpdate
    {
        return $input === null ? new AlbumUpdate() : AlbumUpdate::from($input);
    }

    /**
     * @throws RelationNotIncludedException when the read did not include `artist`
     */
    public function artist(): ?Artist
    {
        $raw = $this->_includedOne('artist', 'artistRef');

        return $raw === null ? null : Artist::_hydrate($raw, $this->_child('artist'));
    }

    /**
     * @return ResourceCollection<Track>
     *
     * @throws RelationNotIncludedException when the read did not include `tracks`
     */
    public function tracks(): ResourceCollection
    {
        $context = $this->_child('tracks');
        $tracks = [];

        foreach ($this->_includedMany('tracks', 'tracksRef') as $raw) {
            $tracks[] = Track::_hydrate($raw, $context);
        }

        return new ResourceCollection($tracks);
    }
}
