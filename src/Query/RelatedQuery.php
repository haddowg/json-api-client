<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Query;

/**
 * How an included to-many relation should come back: filtered and sorted by its own vocabulary,
 * under the Relationship Queries profile.
 *
 * A generated `<Type><Relation>RelatedQuery` extends this and re-declares the doors the relation's
 * endpoint offers, typed by its filter keys and sort tokens. A read's `withX()` takes one as an
 * optional closure, so the shaping sits next to the include it shapes and names the relation once:
 *
 * ```php
 * $client->albums->query()->withTracks(fn ($t) => $t->whereExplicit(false)->sort('title'))->get();
 * ```
 *
 * That sends `relatedQuery[tracks][filter][explicit]=false&relatedQuery[tracks][sort]=title`. The
 * profile has no `page`: the server renders the relation's first page and links the rest.
 */
abstract class RelatedQuery
{
    /**
     * @var array<string, mixed>
     */
    protected array $filter = [];

    /**
     * @var list<string>
     */
    protected array $sort = [];

    final public function __construct() {}

    /**
     * The shaping as the profile's `filter` and `sort` members, leaving out what is unset.
     *
     * @return array{filter?: array<string, mixed>, sort?: list<string>}
     */
    public function toArray(): array
    {
        $out = [];
        if ($this->filter !== []) {
            $out['filter'] = $this->filter;
        }
        if ($this->sort !== []) {
            $out['sort'] = $this->sort;
        }

        return $out;
    }

    /**
     * Filter by values assembled at runtime. Protected here and re-declared public, with a check
     * against the relation's filter keys, by a generated query whose relation declares filters.
     *
     * @param array<string, mixed> $filter
     *
     * @return static
     */
    protected function filterRaw(array $filter): static
    {
        return $this->filtering($filter);
    }

    /**
     * Sort by tokens assembled at runtime. Protected here and re-declared public, with a check
     * against the relation's sort tokens, by a generated query whose relation declares a sort.
     *
     * @param list<string> $tokens in precedence order
     *
     * @return static
     */
    protected function sortRaw(array $tokens): static
    {
        return $this->sorting($tokens);
    }

    /**
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
}
