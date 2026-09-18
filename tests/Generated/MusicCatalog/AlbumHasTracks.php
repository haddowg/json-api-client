<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Collections\ResourceCollection;

/**
 * The `tracks` relation of an `albums` resource, hydrated.
 *
 * A hydrated to-many value is a {@see ResourceCollection} and never a paginated one: the
 * resources arrived inside a compound document's `included`, which carries no page. Paging the
 * relation is what the related endpoint is for.
 *
 * @generated from `components.schemas.AlbumsResource.properties.relationships.tracks`
 */
interface AlbumHasTracks
{
    /**
     * @return ResourceCollection<Track>
     *
     * @throws \haddowg\JsonApiClient\Exceptions\RelationNotIncludedException when the read did not include `tracks`
     */
    public function tracks(): ResourceCollection;

    /** @var ResourceCollection<Track> */
    public ResourceCollection $tracks { get; }
}
