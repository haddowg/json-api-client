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
     * Include paths assembled at runtime, or deeper than one hop (`tracks.album`). Nothing is
     * narrowed.
     *
     * @return static
     */
    public function with(string ...$paths): static
    {
        return parent::with(...$paths);
    }

    /**
     * @param array<string, list<string>> $fields
     *
     * @return static
     */
    public function fields(array $fields): static
    {
        return parent::fields($fields);
    }

    /**
     * @param array<string, mixed> $filter
     *
     * @return static
     */
    public function filterRaw(array $filter): static
    {
        return parent::filterRaw($filter);
    }

    /**
     * @param list<string> $tokens
     *
     * @return static
     */
    public function sortRaw(array $tokens): static
    {
        return parent::sortRaw($tokens);
    }

    /**
     * @param array<string, mixed> $page
     *
     * @return static
     */
    public function pageRaw(array $page): static
    {
        return parent::pageRaw($page);
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
