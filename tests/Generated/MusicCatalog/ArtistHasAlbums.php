<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Collections\ResourceCollection;

/**
 * The `albums` relation of an `artists` resource, hydrated.
 *
 * @generated from `components.schemas.ArtistsResource.properties.relationships.albums`
 */
interface ArtistHasAlbums
{
    /**
     * @return ResourceCollection<Album>
     *
     * @throws \haddowg\JsonApiClient\Exceptions\RelationNotIncludedException when the read did not include `albums`
     */
    public function albums(): ResourceCollection;

    /** @var ResourceCollection<Album> */
    public ResourceCollection $albums { get; }
}
