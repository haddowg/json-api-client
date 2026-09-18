<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Pagination;

use haddowg\JsonApiClient\Support\Wire;

/**
 * An offset-paginated collection: `page[offset]` and `page[limit]`.
 *
 * There is no page number here and no `lastPage`; a caller who wants "how many pages" on an
 * offset paginator is asking the wrong paginator. `total` is the one counted member, and it
 * is null unless the response was counted.
 */
final class Offset extends Page
{
    /**
     * @param array<string, mixed> $meta
     */
    private function __construct(
        public readonly ?int $offset,
        public readonly ?int $limit,
        public readonly ?int $from,
        public readonly ?int $to,
        public readonly ?int $total,
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
            offset: Wire::int($meta, 'offset'),
            limit: Wire::int($meta, 'limit'),
            from: Wire::int($meta, 'from'),
            to: Wire::int($meta, 'to'),
            total: Wire::int($meta, 'total'),
            links: $links,
            meta: $meta,
        );
    }

    public function kind(): PaginatorKind
    {
        return PaginatorKind::Offset;
    }

    /**
     * Whether this response was counted. False is the normal case, not a failure.
     */
    public function isCounted(): bool
    {
        return $this->total !== null;
    }
}
