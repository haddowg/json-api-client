<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Query;

/**
 * The query-parameter families a read can carry, and how they reach the wire.
 *
 * Bracketed keys are kept literal (`filter[title]`, not `filter%5Btitle%5D`) because that is
 * what JSON:API servers match on; only values are encoded. Family order is fixed and insertion
 * order is preserved within a family, so the same query always produces the same URL — which
 * matters for caching and for asserting on requests in tests.
 */
final class ReadQuery
{
    /**
     * @param array<string, mixed>        $filter    values by filter name; a nested map is a
     *                                               structured filter (`filter[releasedAt][min]`)
     * @param list<string>                $sort      signed tokens, in precedence order
     * @param list<string>                $include   relation paths
     * @param array<string, list<string>> $fields    sparse fieldsets by resource type
     * @param list<string>                $withCount relationship count tokens
     * @param array<string, mixed>        $page      paginator parameters (`number`, `after`, …)
     */
    public function __construct(
        public readonly array $filter = [],
        public readonly array $sort = [],
        public readonly array $include = [],
        public readonly array $fields = [],
        public readonly array $withCount = [],
        public readonly array $page = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->filter === []
            && $this->sort === []
            && $this->include === []
            && $this->fields === []
            && $this->withCount === []
            && $this->page === [];
    }

    /**
     * Serialise to a query string with no leading `?`.
     */
    public function toQueryString(): string
    {
        $parts = [];

        foreach ($this->filter as $name => $value) {
            self::appendFilter($parts, 'filter[' . $name . ']', $value);
        }

        self::append($parts, 'sort', \implode(',', $this->sort));
        self::append($parts, 'include', \implode(',', $this->include));

        foreach ($this->fields as $type => $names) {
            // An empty fieldset is meaningful — it selects no members of that type — so it is
            // emitted rather than skipped as an empty value would normally be.
            $parts[] = 'fields[' . $type . ']=' . \rawurlencode(\implode(',', $names));
        }

        self::append($parts, 'withCount', \implode(',', $this->withCount));

        foreach ($this->page as $key => $value) {
            self::append($parts, 'page[' . $key . ']', $value);
        }

        return \implode('&', $parts);
    }

    /**
     * Append a URI's query string to it, whichever separator it needs.
     */
    public function appendTo(string $uri): string
    {
        $query = $this->toQueryString();

        if ($query === '') {
            return $uri;
        }

        return $uri . (\str_contains($uri, '?') ? '&' : '?') . $query;
    }

    /**
     * A structured filter value (a range's `{min, max}`) recurses into nested bracketed keys,
     * matching the deepObject shape the server reads. Without that a map would reach the wire
     * as `filter[releasedAt]=Array` and be quietly ignored.
     *
     * @param list<string> $parts
     */
    private static function appendFilter(array &$parts, string $key, mixed $value): void
    {
        if (\is_array($value) && !\array_is_list($value)) {
            foreach ($value as $sub => $nested) {
                self::appendFilter($parts, $key . '[' . $sub . ']', $nested);
            }

            return;
        }

        self::append($parts, $key, $value);
    }

    /**
     * @param list<string> $parts
     */
    private static function append(array &$parts, string $key, mixed $value): void
    {
        $encoded = self::stringify($value);

        if ($encoded === null || $encoded === '') {
            return;
        }

        $parts[] = $key . '=' . \rawurlencode($encoded);
    }

    /**
     * Booleans go over the wire as `true`/`false`, not PHP's `1`/`''` — a filter that reads
     * `filter[approved]=1` is not the one the server documented.
     */
    private static function stringify(mixed $value): ?string
    {
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (\is_array($value)) {
            $members = [];
            foreach ($value as $member) {
                $member = self::stringify($member);
                if ($member !== null) {
                    $members[] = $member;
                }
            }

            return \implode(',', $members);
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        // Anything left that is not a scalar has no defensible wire form; dropping it beats
        // sending `Array` or an object hash the server will reject.
        return \is_scalar($value) ? (string) $value : null;
    }
}
