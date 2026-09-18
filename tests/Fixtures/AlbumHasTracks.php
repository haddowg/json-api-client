<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

/**
 * Stands in for a generated relation marker.
 */
interface AlbumHasTracks
{
    /**
     * @return list<string>
     */
    public function tracks(): array;
}
