<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Pagination;

use haddowg\JsonApiClient\Support\Wire;

/**
 * A page-numbered collection: `page[number]` and `page[size]`.
 *
 * `total` and `lastPage` are the two members counting buys you. Both are null in count-free
 * mode, which is the default — the server only counts when asked, and the way to ask is
 * `withCount('_self_')`, whose result lands in the same `meta.page` block this reads.
 */
final class PageNumber extends Page
{
    /**
     * @param array<string, mixed> $meta
     */
    private function __construct(
        public readonly ?int $number,
        public readonly ?int $size,
        public readonly ?int $from,
        public readonly ?int $to,
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
            size: Wire::int($meta, 'perPage'),
            from: Wire::int($meta, 'from'),
            to: Wire::int($meta, 'to'),
            total: Wire::int($meta, 'total'),
            lastPage: Wire::int($meta, 'lastPage'),
            links: $links,
            meta: $meta,
        );
    }

    public function kind(): PaginatorKind
    {
        return PaginatorKind::PageNumber;
    }

    /**
     * Whether this response was counted. False is the normal case, not a failure.
     */
    public function isCounted(): bool
    {
        return $this->total !== null;
    }
}
