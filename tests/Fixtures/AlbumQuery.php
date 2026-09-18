<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

use haddowg\JsonApiClient\Query\Query;

/**
 * Stands in for a generated query builder: the narrowing `withX()` methods and the typed
 * doors codegen emits over the runtime primitives.
 *
 * @template TProj of object
 *
 * @extends Query<TProj>
 */
final class AlbumQuery extends Query
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

    /**
     * @return static<TProj&AlbumHasTracks>
     */
    public function withTracks(): static
    {
        return $this->including('tracks');
    }

    /**
     * @param 'releasedAt'|'-releasedAt'|'-title'|'title' ...$tokens
     *
     * @return static
     */
    public function sort(string ...$tokens): static
    {
        return $this->sorting(\array_values($tokens));
    }

    /**
     * @param list<'_self_'|'tracks'> $tokens
     *
     * @return static
     */
    public function withCount(array $tokens): static
    {
        return $this->counting($tokens);
    }

    /**
     * @return static
     */
    public function page(int $number, ?int $size = null): static
    {
        return $this->paging($size === null ? ['number' => $number] : ['number' => $number, 'size' => $size]);
    }

    /**
     * @return static
     */
    public function whereTitle(string $title): static
    {
        return $this->filtering(['title' => $title]);
    }
}
