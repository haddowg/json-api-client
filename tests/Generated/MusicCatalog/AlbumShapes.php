<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Support\Identifier;

/**
 * Every array shape the `albums` surface accepts, in one place.
 *
 * `@phpstan-type` aliases are class-local, so they have to be declared somewhere and imported
 * everywhere they are used. That import is not optional bookkeeping: without a matching
 * `@phpstan-import-type` the alias silently degrades to bare `array`, nothing is reported, and
 * every array-shape check on that door is lost — the exact failure the typed array door exists
 * to prevent.
 *
 * The filter aliases are only as precise as the document allows. `filter[title]`, `filter[q]`,
 * `filter[tracks]` and `filter[artist.name]` are emitted with `"schema": {}`, which carries no
 * type at all, so their values are `mixed` here. `filter[rating]` and `filter[releasedAt]`
 * declare a `deepObject` range and are typed. Tightening the empty ones is a server-side change;
 * regenerating picks it up with no change to calling code.
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
 * @phpstan-type AlbumFilterShape array{tracks?: mixed, 'artist.name'?: mixed, title?: mixed, rating?: AlbumRangeShape, releasedAt?: AlbumDateRangeShape, q?: mixed}
 * @phpstan-type AlbumSortToken '-releasedAt'|'-status'|'-title'|'releasedAt'|'status'|'title'
 * @phpstan-type AlbumIncludePath 'artist'|'artist.albums'|'tracks'|'tracks.album'|'tracks.playlists'
 * @phpstan-type AlbumFieldToken 'artist'|'artwork'|'availableFrom'|'availableUntil'|'averageRating'|'explicit'|'releaseInfo'|'releasedAt'|'status'|'title'|'tracks'
 * @phpstan-type AlbumCountToken 'tracks'
 *
 * @generated from the `albums` operations of the Music Catalog API
 */
final class AlbumShapes
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
}
