<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Pagination;

use haddowg\JsonApiClient\Support\Wire;

/**
 * A page-numbered collection whose page size the server fixes: `page[number]` only.
 *
 * `total` and `lastPage` arrive only when the response was counted, through
 * `withCount('_self_')`. In count-free mode they are null, and `next` alone says whether a
 * further page follows.
 */
final class FixedPage extends Page
{
    /**
     * @param array<string, mixed> $meta
     */
    private function __construct(
        public readonly ?int $number,
        public readonly ?int $total,
        public readonly ?int $lastPage,
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
        return new self(
            number: Wire::int($meta, 'currentPage'),
            total: Wire::int($meta, 'total'),
            lastPage: Wire::int($meta, 'lastPage'),
            links: $links,
            meta: $meta,
        );
    }

    public function kind(): PaginatorKind
    {
        return PaginatorKind::Fixed;
    }

    /**
     * Whether this response was counted. False is the normal case, not a failure.
     */
    public function isCounted(): bool
    {
        return $this->total !== null;
    }
}
