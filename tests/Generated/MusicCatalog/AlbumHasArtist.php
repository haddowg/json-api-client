<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

/**
 * The `artist` relation of an `albums` resource, hydrated.
 *
 * A marker carries no implementation. Its only job is to be intersected into a query builder's
 * projection by `withArtist()`, which is what makes `$album->artist` type-check after the
 * include and a compile error without it.
 *
 * The property hook is emitted only on an 8.4 target and mirrors the method exactly, so
 * `$album->artist` and `$album->artist()` are the same value read the same way.
 *
 * @generated from `components.schemas.AlbumsResource.properties.relationships.artist`
 */
interface AlbumHasArtist
{
    /**
     * The related `artists` resource, or null when the album has no artist.
     *
     * @throws \haddowg\JsonApiClient\Exceptions\RelationNotIncludedException when the read did not include `artist`
     */
    public function artist(): ?Artist;

    public ?Artist $artist { get; }
}
