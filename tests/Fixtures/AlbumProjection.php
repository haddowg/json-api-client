<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

use haddowg\JsonApiClient\Query\Projection;

/**
 * Stands in for a generated projection builder — the write-side surface, which must carry
 * `withX()` and `fields()` and nothing a write response would reject.
 *
 * @template TProj of object
 *
 * @extends Projection<TProj>
 */
final class AlbumProjection extends Projection
{
    /**
     * @return self<AlbumBase>
     */
    public static function make(): self
    {
        /** @var self<AlbumBase> */
        return new self();
    }

    /**
     * @return static<TProj&AlbumHasArtist>
     */
    public function withArtist(): static
    {
        return $this->including('artist');
    }
}
