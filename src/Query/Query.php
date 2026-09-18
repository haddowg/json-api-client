<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Query;

/**
 * A read: a projection, plus the concerns only a read has.
 *
 * Extending {@see Projection} rather than duplicating it means a generated `AlbumQuery` and a
 * generated `AlbumProjection` share one set of `withX()` methods, and the split still genuinely
 * excludes `sort`/`filter`/`page` from a write's projection.
 *
 * The loose doors (`filterRaw`, `sortRaw`, `pageRaw`) accept what a literal-typed method
 * cannot: values assembled at runtime. They carry no static guarantee by design, which is why
 * generated subclasses override them to validate the tokens against the descriptor before
 * delegating here. A typo'd filter name that would otherwise return a quietly unfiltered
 * collection becomes a throw naming the mistake.
 *
 * @template TProj of object
 *
 * @extends Projection<TProj>
 */
abstract class Query extends Projection
{
    /**
     * @var array<string, mixed>
     */
    protected array $filter = [];

    /**
     * @var list<string>
     */
    protected array $sort = [];

    /**
     * @var list<string>
     */
    protected array $withCount = [];

    /**
     * @var array<string, mixed>
     */
    protected array $page = [];

    /**
     * Filter by values assembled at runtime, checked by nothing statically.
     *
     * @param array<string, mixed> $filter
     *
     * @return static
     */
    public function filterRaw(array $filter): static
    {
        return $this->filtering($filter);
    }

    /**
     * Sort by tokens assembled at runtime.
     *
     * The typed door takes a variadic of the descriptor's literal tokens, which a
     * `list<string>` built at runtime can never satisfy — hence this one.
     *
     * @param list<string> $tokens in precedence order
     *
     * @return static
     */
    public function sortRaw(array $tokens): static
    {
        return $this->sorting($tokens);
    }

    /**
     * Set paginator parameters directly (`['number' => 2, 'size' => 50]`).
     *
     * @param array<string, mixed> $page
     *
     * @return static
     */
    public function pageRaw(array $page): static
    {
        return $this->paging($page);
    }

    /**
     * The read as query parameters.
     */
    public function toReadQuery(): ReadQuery
    {
        return new ReadQuery(
            filter: $this->filter,
            sort: $this->sort,
            include: $this->include,
            fields: $this->fields,
            withCount: $this->withCount,
            page: $this->page,
        );
    }

    /**
     * Merge filter values, last write winning per name.
     *
     * @param array<string, mixed> $filter
     *
     * @return static
     */
    protected function filtering(array $filter): static
    {
        $clone = clone $this;
        $clone->filter = [...$clone->filter, ...$filter];

        return $clone;
    }

    /**
     * Append sort tokens. Argument order is precedence, so tokens accumulate rather than
     * replace — nothing has to be translated between the call and the wire.
     *
     * @param list<string> $tokens
     *
     * @return static
     */
    protected function sorting(array $tokens): static
    {
        $clone = clone $this;
        $clone->sort = [...$clone->sort, ...$tokens];

        return $clone;
    }

    /**
     * Add relationship count tokens. The Countable profile is negotiated whenever this is
     * non-empty, so the server does not reject the parameter it advertised.
     *
     * @param list<string> $tokens
     *
     * @return static
     */
    protected function counting(array $tokens): static
    {
        $clone = clone $this;

        foreach ($tokens as $token) {
            if (!\in_array($token, $clone->withCount, true)) {
                $clone->withCount[] = $token;
            }
        }

        return $clone;
    }

    /**
     * @param array<string, mixed> $page
     *
     * @return static
     */
    protected function paging(array $page): static
    {
        $clone = clone $this;
        $clone->page = [...$clone->page, ...$page];

        return $clone;
    }
}
