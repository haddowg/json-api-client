<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Collections;

use haddowg\JsonApiClient\Pagination\Page;

/**
 * A page of a paginated collection, plus the means to walk the rest.
 *
 * `TPage` is the exact paginator kind, not the {@see Page} base, so the page a collection
 * carries is as specific as the endpoint that produced it: `$albums->_page()->after` on a
 * page-numbered collection is a static error, not a null at runtime.
 *
 * Navigation follows the server's links rather than doing arithmetic on a total, which is what
 * makes it correct in count-free mode — the usual mode, since counting is opt-in.
 *
 * @template T
 * @template TPage of Page
 *
 * @extends ResourceCollection<T>
 */
class PaginatedCollection extends ResourceCollection
{
    /**
     * @var TPage
     */
    private readonly Page $page;

    /**
     * Re-fetches a page link and returns the collection it resolves to.
     *
     * @var (\Closure(string): (self<T, TPage>|null))|null
     */
    private readonly ?\Closure $navigator;

    /**
     * @param list<T>                                       $items
     * @param TPage                                         $page
     * @param array<string, mixed>                          $meta
     * @param array<string, mixed>                          $links
     * @param (\Closure(string): (self<T, TPage>|null))|null $navigator
     */
    public function __construct(
        array $items,
        Page $page,
        array $meta = [],
        array $links = [],
        ?\Closure $navigator = null,
    ) {
        parent::__construct($items, $meta, $links);

        $this->page = $page;
        $this->navigator = $navigator;
    }

    /**
     * @return TPage
     */
    public function _page(): Page
    {
        return $this->page;
    }

    /**
     * The next page, or null when this is the last one.
     *
     * @return self<T, TPage>|null
     */
    public function _next(): ?self
    {
        return $this->navigate($this->page->next());
    }

    /**
     * The previous page, or null when this is the first one.
     *
     * @return self<T, TPage>|null
     */
    public function _prev(): ?self
    {
        return $this->navigate($this->page->prev());
    }

    /**
     * Every member from here to the end of the collection, fetching each page as it is reached.
     *
     * Lazy: nothing beyond the current page is requested until the iteration asks for it, and
     * a `break` costs nothing. Keys run unbroken across page boundaries.
     *
     * @return \Generator<int, T, mixed, void>
     */
    public function _autoPaging(): \Generator
    {
        $page = $this;
        $index = 0;

        while ($page !== null) {
            foreach ($page as $member) {
                yield $index++ => $member;
            }

            $page = $page->_next();
        }
    }

    /**
     * @return self<T, TPage>|null
     */
    private function navigate(?string $url): ?self
    {
        if ($url === null) {
            return null;
        }

        if ($this->navigator === null) {
            throw new \LogicException(
                'This collection cannot follow its pagination links: it was built without a navigator.',
            );
        }

        return ($this->navigator)($url);
    }
}
