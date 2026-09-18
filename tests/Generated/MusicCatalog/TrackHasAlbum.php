<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

/**
 * The `album` relation of a `tracks` resource, hydrated.
 *
 * This marker is what makes the depth rule visible. `$album->tracks[0]->album` compiles because
 * `Track` implements it, and throws at runtime unless `tracks.album` was included — narrowing
 * covers depth 1, the throw covers every depth.
 *
 * @generated from `components.schemas.TracksResource.properties.relationships.album`
 */
interface TrackHasAlbum
{
    /**
     * @throws \haddowg\JsonApiClient\Exceptions\RelationNotIncludedException when the read did not include `album` at this depth
     */
    public function album(): ?Album;

    public ?Album $album { get; }
}
