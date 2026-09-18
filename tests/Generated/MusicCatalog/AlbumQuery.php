<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Collections\PaginatedCollection;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\Forbidden;
use haddowg\JsonApiClient\Exceptions\NotAcceptable;
use haddowg\JsonApiClient\Exceptions\ServerError;
use haddowg\JsonApiClient\Exceptions\TransportException;
use haddowg\JsonApiClient\Exceptions\Unauthorized;
use haddowg\JsonApiClient\Exceptions\UnknownFilterException;
use haddowg\JsonApiClient\Exceptions\UnknownSortException;
use haddowg\JsonApiClient\Http\Request;
use haddowg\JsonApiClient\Pagination\PageLinks;
use haddowg\JsonApiClient\Pagination\PageNumber;
use haddowg\JsonApiClient\Query\Query;
use haddowg\JsonApiClient\Query\ReadQuery;
use haddowg\JsonApiClient\Resources\Fetcher;
use haddowg\JsonApiClient\Resources\ResourceContext;
use haddowg\JsonApiClient\Support\QueryConditionable;

/**
 * A read of `GET /albums`: the projection, plus everything only a read can carry.
 *
 * Every door the endpoint advertises is here and nothing it does not. `page(int, ?int)` exists
 * because the operation declares `page[number]` and `page[size]`; a cursor-paginated endpoint
 * would get `after`/`before` instead, and an unpaginated one would get neither along with no
 * `_page()` on its results.
 *
 * Filters come in three doors on purpose. `whereTitle()` catches a typo'd filter *name* as an
 * undefined method. `filter()` takes the typed shape. `filterRaw()` is the explicit loose door.
 * They are separate methods because a single parameter typed
 * `AlbumFilterShape|array<string, mixed>` silently accepts anything — the permissive branch
 * swallows the shape and the checking is worth exactly nothing. Both array doors still validate
 * names at runtime: every filter is optional, so a typo lands in the one blind spot the static
 * types have, and a quietly unfiltered collection looks like data rather than a bug.
 *
 * @template TProj of object
 *
 * @extends Query<TProj>
 *
 * @phpstan-import-type AlbumCountToken from AlbumShapes
 * @phpstan-import-type AlbumDateRangeShape from AlbumShapes
 * @phpstan-import-type AlbumFilterShape from AlbumShapes
 * @phpstan-import-type AlbumIncludePath from AlbumShapes
 * @phpstan-import-type AlbumRangeShape from AlbumShapes
 * @phpstan-import-type AlbumSortToken from AlbumShapes
 *
 * @generated from `GET /albums` (operationId `fetchCollection.albums`)
 */
final class AlbumQuery extends Query
{
    /** @use AlbumProjecting<TProj> */
    use AlbumProjecting;
    use QueryConditionable;

    public function __construct(private readonly Fetcher $fetcher) {}

    /**
     * Filter by `tracks`.
     *
     * The value is `mixed` because the document emits `"schema": {}` for this filter, which
     * states no type at all. Regenerating against a server that declares the filter's value
     * schema tightens this with no change to calling code.
     *
     * @return static
     */
    public function whereTracks(mixed $value): static
    {
        return $this->filtering(['tracks' => $value]);
    }

    /**
     * Filter by `artist.name`.
     *
     * @return static
     */
    public function whereArtistName(mixed $value): static
    {
        return $this->filtering(['artist.name' => $value]);
    }

    /**
     * Matches values containing the given substring.
     *
     * @return static
     */
    public function whereTitle(mixed $value): static
    {
        return $this->filtering(['title' => $value]);
    }

    /**
     * Matches values within the given inclusive numeric range (min/max, either optional).
     *
     * @param AlbumRangeShape $range
     *
     * @return static
     */
    public function whereRating(array $range): static
    {
        return $this->filtering(['rating' => $range]);
    }

    /**
     * Matches values within the given inclusive date-time range (min/max ISO-8601, either
     * optional).
     *
     * @param AlbumDateRangeShape $range
     *
     * @return static
     */
    public function whereReleasedAt(array $range): static
    {
        return $this->filtering(['releasedAt' => $range]);
    }

    /**
     * Case-insensitive substring search across title.
     *
     * @return static
     */
    public function whereQ(mixed $value): static
    {
        return $this->filtering(['q' => $value]);
    }

    /**
     * The typed array door: values are checked, including nested ranges, and names are checked
     * at runtime.
     *
     * @param AlbumFilterShape $filter
     *
     * @return static
     *
     * @throws UnknownFilterException when a name is not one this endpoint filters by
     */
    public function filter(array $filter): static
    {
        return $this->filtering(self::guardFilters($filter));
    }

    /**
     * The loose door, for a filter map assembled at runtime. Nothing is checked statically; the
     * names are still checked here.
     *
     * @param array<string, mixed> $filter
     *
     * @return static
     *
     * @throws UnknownFilterException when a name is not one this endpoint filters by
     */
    public function filterRaw(array $filter): static
    {
        return $this->filtering(self::guardFilters($filter));
    }

    /**
     * Sort by the endpoint's own tokens, in precedence order.
     *
     * Argument order is precedence, so nothing has to be translated between the call and the
     * wire. A typo'd field, a real-but-unsortable one, and `+releasedAt` are all rejected before
     * the request exists, and a multi-argument mistake names the offending position.
     *
     * @param AlbumSortToken ...$tokens
     *
     * @return static
     */
    public function sort(string ...$tokens): static
    {
        return $this->sorting(\array_values($tokens));
    }

    /**
     * The loose sort door, for tokens assembled at runtime — a `list<string>` can never satisfy
     * a literal union, which is the whole reason this exists.
     *
     * @param list<string> $tokens
     *
     * @return static
     *
     * @throws UnknownSortException when a token is not one this endpoint sorts by
     */
    public function sortRaw(array $tokens): static
    {
        foreach ($tokens as $token) {
            if (!\in_array($token, AlbumShapes::SORT_TOKENS, true)) {
                throw UnknownSortException::for($token, AlbumShapes::SORT_TOKENS);
            }
        }

        return $this->sorting($tokens);
    }

    /**
     * Count related resources without including them, under the Countable profile.
     *
     * `GET /albums` advertises `tracks` and nothing else. Its own parameter description mentions
     * `_self_` — the token that puts a collection total in `meta.page` — but the enum does not
     * list it, so it is not generated and `_page()->total` stays null on this endpoint.
     *
     * A counted relation is still not an included one: `$album->tracks` throws, and the count is
     * read through `$album->_rel('tracks')->total()`.
     *
     * @param list<AlbumCountToken> $tokens
     *
     * @return static
     */
    public function withCount(array $tokens): static
    {
        return $this->counting($tokens);
    }

    /**
     * @param positive-int      $number
     * @param positive-int|null $size
     *
     * @return static
     */
    public function page(int $number, ?int $size = null): static
    {
        return $this->paging($size === null ? ['number' => $number] : ['number' => $number, 'size' => $size]);
    }

    /**
     * Run the read.
     *
     * @return PaginatedCollection<TProj, PageNumber>
     *
     * @throws BadRequest          when a query parameter is rejected
     * @throws Unauthorized        when credentials are missing or invalid
     * @throws Forbidden           when the read is not permitted
     * @throws NotAcceptable       when the negotiated media type cannot be satisfied
     * @throws ServerError         when the server fails
     * @throws TransportException  when no response is received at all
     */
    public function get(): PaginatedCollection
    {
        $query = $this->toReadQuery();

        return $this->collect($this->fetcher->read($this->request($query), $query), $query);
    }

    /**
     * The first album the read matches, fetching a single-resource page rather than a whole one.
     *
     * @return TProj|null
     *
     * @throws BadRequest          when a query parameter is rejected
     * @throws Unauthorized        when credentials are missing or invalid
     * @throws Forbidden           when the read is not permitted
     * @throws NotAcceptable       when the negotiated media type cannot be satisfied
     * @throws ServerError         when the server fails
     * @throws TransportException  when no response is received at all
     */
    public function first(): ?object
    {
        return $this->page(1, 1)->get()->first();
    }

    /**
     * @param AlbumFilterShape|array<string, mixed> $filter
     *
     * @return array<string, mixed>
     *
     * @throws UnknownFilterException
     */
    private static function guardFilters(array $filter): array
    {
        foreach ($filter as $name => $value) {
            $name = (string) $name;

            if (!\in_array($name, AlbumShapes::FILTERS, true)) {
                throw UnknownFilterException::for($name, AlbumShapes::FILTERS);
            }

            $members = AlbumShapes::FILTER_MEMBERS[$name] ?? null;

            if ($members === null || !\is_array($value)) {
                continue;
            }

            $qualified = \array_map(static fn(string $member): string => $name . '[' . $member . ']', $members);

            foreach (\array_keys($value) as $member) {
                if (!\in_array((string) $member, $members, true)) {
                    throw UnknownFilterException::for($name . '[' . (string) $member . ']', $qualified);
                }
            }
        }

        return $filter;
    }

    /**
     * The Countable profile is negotiated only when a request actually uses a `withCount`
     * parameter, which is what `x-profile` on that parameter already states.
     */
    private function request(ReadQuery $query): Request
    {
        $request = Request::get($query->appendTo('/albums'));

        return $query->withCount === [] ? $request : $request->withProfiles([AlbumShapes::COUNTABLE_PROFILE]);
    }

    /**
     * @return PaginatedCollection<TProj, PageNumber>
     */
    private function collect(ResourceContext $context, ReadQuery $query): PaginatedCollection
    {
        $document = $context->document();
        $albums = [];

        foreach ($context->primaryList() as $raw) {
            $albums[] = $this->hydrate($raw, $context);
        }

        return new PaginatedCollection(
            $albums,
            PageNumber::read($document->pageMeta(), PageLinks::fromArray($document->links())),
            $document->meta(),
            $document->links(),
            fn(string $url): PaginatedCollection => $this->collect($this->fetcher->readUrl($url, $query), $query),
        );
    }

    /**
     * The one assertion the design cannot avoid.
     *
     * The runtime always builds an {@see Album}, and `Album` implements every marker — but
     * `TProj` is a template parameter, so no bound can prove `Album` satisfies it, and typing
     * the result `Album&TProj` would collapse to plain `Album` and undo every narrowing.
     * {@see ResourceContext::hydrate()} erases the concrete class so the assertion is legal;
     * one of these per generated class is the whole cost.
     *
     * @param array<string, mixed> $raw
     *
     * @return TProj
     */
    private function hydrate(array $raw, ResourceContext $context): object
    {
        /** @var TProj */
        return $context->hydrate($raw, Album::_hydrate(...));
    }
}
