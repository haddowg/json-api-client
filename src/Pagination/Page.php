<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Pagination;

use haddowg\JsonApiClient\Support\Wire;

/**
 * Where a paginated collection sits, in whichever terms its paginator uses.
 *
 * A collection's page type is its paginator kind, so there is no nullable field to guard and
 * no accessor that means nothing: a cursor collection has `after`, a page-numbered one has
 * `number` and `total`, and asking either for the other's is a static error. Where pagination
 * is meaningless the collection has no `_page()` at all.
 *
 * Every counted member is `?int`, because counting is opt-in — a count-free response omits
 * `total` and `lastPage` entirely, and navigation still works off the links.
 */
abstract class Page
{
    /**
     * @param array<string, mixed> $meta the raw `meta.page` block this was read from
     */
    protected function __construct(
        private readonly PageLinks $links,
        private readonly array $meta,
    ) {}

    /**
     * Read the page a document (or relationship object) describes.
     *
     * Returns null for {@see PaginatorKind::None} — an unpaginated collection has no page,
     * which is why `_page()` is absent on one rather than returning null.
     *
     * @param array<string, mixed> $meta  the enclosing `meta` member, whose `page` block is read
     * @param array<string, mixed> $links the enclosing `links` member
     */
    public static function for(PaginatorKind $kind, array $meta, array $links): ?self
    {
        $pageMeta = Wire::map($meta, 'page');
        $pageLinks = PageLinks::fromArray($links);

        return match ($kind) {
            PaginatorKind::PageNumber => PageNumber::read($pageMeta, $pageLinks),
            PaginatorKind::Offset => Offset::read($pageMeta, $pageLinks),
            PaginatorKind::Cursor => Cursor::read($pageMeta, $pageLinks),
            PaginatorKind::None => null,
        };
    }

    abstract public function kind(): PaginatorKind;

    public function links(): PageLinks
    {
        return $this->links;
    }

    /**
     * The raw `meta.page` block, for anything a server adds beyond what the kind models.
     *
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    public function first(): ?string
    {
        return $this->links->first;
    }

    public function prev(): ?string
    {
        return $this->links->prev;
    }

    public function next(): ?string
    {
        return $this->links->next;
    }

    /**
     * Absent in count-free mode, where the server does not know which page is last.
     */
    public function last(): ?string
    {
        return $this->links->last;
    }

    public function hasNext(): bool
    {
        return $this->links->next !== null;
    }

    public function hasPrev(): bool
    {
        return $this->links->prev !== null;
    }
}
