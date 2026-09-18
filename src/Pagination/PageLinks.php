<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Pagination;

use haddowg\JsonApiClient\Support\Wire;

/**
 * The four pagination link relations, resolved to URLs.
 *
 * Navigation is driven by link presence rather than by arithmetic over a total, which is what
 * keeps it correct in count-free mode: a server that does not count omits `last` and derives
 * `next` from whether another row exists.
 */
final class PageLinks
{
    public function __construct(
        public readonly ?string $first = null,
        public readonly ?string $prev = null,
        public readonly ?string $next = null,
        public readonly ?string $last = null,
    ) {}

    /**
     * @param array<string, mixed> $links a document's or relationship object's `links` member
     */
    public static function fromArray(array $links): self
    {
        return new self(
            Wire::href($links['first'] ?? null),
            Wire::href($links['prev'] ?? null),
            Wire::href($links['next'] ?? null),
            Wire::href($links['last'] ?? null),
        );
    }

    /**
     * Read one query parameter out of a page link.
     *
     * The cursor a caller needs to resume from is only ever stated in the `next`/`prev` links,
     * so it is read back out of them rather than invented.
     */
    public static function param(?string $link, string $name): ?string
    {
        if ($link === null) {
            return null;
        }

        $query = \parse_url($link, \PHP_URL_QUERY);

        if (!\is_string($query)) {
            return null;
        }

        \parse_str($query, $params);
        $page = $params['page'] ?? null;

        if (!\is_array($page)) {
            return null;
        }

        $value = $page[$name] ?? null;

        return \is_string($value) ? $value : null;
    }
}
