<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

/**
 * Stands in for a generated relation marker. Intersecting one into a query builder's
 * projection is what makes `include` statically checkable.
 */
interface AlbumHasArtist
{
    public function artist(): string;
}
