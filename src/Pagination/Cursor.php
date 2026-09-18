<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Pagination;

use haddowg\JsonApiClient\Support\Wire;

/**
 * A cursor-paginated collection: `page[after]` / `page[before]`, with `page[size]`.
 *
 * Cursor pagination never counts, so there is no `total` and no `lastPage` to expose and no
 * `last` link to follow. `hasMore` is the server's answer to "is there another page", and it
 * is the only forward signal you get.
 *
 * `after` and `before` are the cursors to resume from, read back out of the `next` and `prev`
 * links — the server states them there and nowhere else. `from` and `to` are this page's own
 * boundary cursors, from `meta.page`.
 */
final class Cursor extends Page
{
    /**
     * @param array<string, mixed> $meta
     */
    private function __construct(
        public readonly ?string $after,
        public readonly ?string $before,
        public readonly ?string $from,
        public readonly ?string $to,
        public readonly ?int $size,
        public readonly bool $hasMore,
        PageLinks $links,
        array $meta,
    ) {
        parent::__construct($links, $meta);
    }

    /**
     * @param array<string, mixed> $meta the `meta.page` block
     */
    public static function read(array $meta, PageLinks $links): self
    {
        $hasMore = $meta['hasMore'] ?? null;

        return new self(
            after: PageLinks::param($links->next, 'after'),
            before: PageLinks::param($links->prev, 'before'),
            from: self::cursor($meta, 'from'),
            to: self::cursor($meta, 'to'),
            size: Wire::int($meta, 'perPage'),
            // A server that omits `hasMore` still emits a `next` link when there is more.
            hasMore: \is_bool($hasMore) ? $hasMore : $links->next !== null,
            links: $links,
            meta: $meta,
        );
    }

    public function kind(): PaginatorKind
    {
        return PaginatorKind::Cursor;
    }

    /**
     * A boundary cursor, which is a row key and so may arrive as a number or a string.
     *
     * @param array<string, mixed> $meta
     */
    private static function cursor(array $meta, string $key): ?string
    {
        $value = $meta[$key] ?? null;

        return \is_string($value) || \is_int($value) ? (string) $value : null;
    }
}
