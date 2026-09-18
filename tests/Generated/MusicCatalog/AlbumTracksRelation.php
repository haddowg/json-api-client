<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Collections\PaginatedCollection;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\Forbidden;
use haddowg\JsonApiClient\Exceptions\NotFound;
use haddowg\JsonApiClient\Exceptions\ServerError;
use haddowg\JsonApiClient\Exceptions\TransportException;
use haddowg\JsonApiClient\Exceptions\Unauthorized;
use haddowg\JsonApiClient\Exceptions\ValidationFailed;
use haddowg\JsonApiClient\Http\Request;
use haddowg\JsonApiClient\Pagination\PageLinks;
use haddowg\JsonApiClient\Pagination\PageNumber;
use haddowg\JsonApiClient\Query\ReadQuery;
use haddowg\JsonApiClient\Resources\Fetcher;
use haddowg\JsonApiClient\Resources\ResourceContext;
use haddowg\JsonApiClient\Support\Identifier;

/**
 * The `tracks` relationship of one album.
 *
 * A to-many whose relationship endpoint declares `POST`, `DELETE` and `PATCH`, so all three
 * verbs are generated. `tracks/{id}/relationships/playlists` in the same document declares only
 * `POST` and `DELETE`, and its handle would have no `replace()` at all — the rule is the
 * contract, not the cardinality.
 *
 * Both reads are paginated because both endpoints advertise `page[number]` and `page[size]`,
 * which is the relation's own paginator resolved ahead of the related type's.
 *
 * @generated from `/albums/{id}/tracks` and `/albums/{id}/relationships/tracks`
 */
final class AlbumTracksRelation
{
    public function __construct(
        private readonly Fetcher $fetcher,
        private readonly string $id,
    ) {}

    /**
     * The linkage alone, a page at a time.
     *
     * @return PaginatedCollection<Identifier, PageNumber>
     *
     * @throws BadRequest         when a query parameter is rejected
     * @throws Unauthorized       when credentials are missing or invalid
     * @throws Forbidden          when the read is not permitted
     * @throws NotFound           when no album has this id
     * @throws ServerError        when the server fails
     * @throws TransportException when no response is received at all
     */
    public function get(): PaginatedCollection
    {
        return $this->linkage(
            $this->fetcher->read(Request::get('/albums/' . $this->id . '/relationships/tracks')),
        );
    }

    /**
     * The tracks themselves, a page at a time.
     *
     * The endpoint also advertises `filter`, `sort`, `include`, `fields` and `withCount`. Those
     * need a query builder scoped to the relation, which is a generated class this artefact does
     * not carry — see the README.
     *
     * @return PaginatedCollection<Track, PageNumber>
     *
     * @throws BadRequest         when a query parameter is rejected
     * @throws Unauthorized       when credentials are missing or invalid
     * @throws Forbidden          when the read is not permitted
     * @throws NotFound           when no album has this id
     * @throws ServerError        when the server fails
     * @throws TransportException when no response is received at all
     */
    public function related(): PaginatedCollection
    {
        return $this->resources($this->fetcher->read(Request::get('/albums/' . $this->id . '/tracks')));
    }

    /**
     * Add tracks to the relationship, leaving the rest alone.
     *
     * @param list<Identifier|Track> $tracks
     *
     * @throws BadRequest         when the document is malformed
     * @throws Unauthorized       when credentials are missing or invalid
     * @throws Forbidden          when the write is not permitted
     * @throws NotFound           when no album has this id
     * @throws ValidationFailed   when the linkage fails validation
     * @throws ServerError        when the server fails
     * @throws TransportException when no response is received at all
     */
    public function add(array $tracks): void
    {
        $this->fetcher->send(Request::post(
            '/albums/' . $this->id . '/relationships/tracks',
            \json_encode(['data' => AlbumWrite::linkageList($tracks)], \JSON_THROW_ON_ERROR),
        ));
    }

    /**
     * Remove tracks from the relationship, leaving the rest alone.
     *
     * @param list<Identifier|Track> $tracks
     *
     * @throws BadRequest         when the document is malformed
     * @throws Unauthorized       when credentials are missing or invalid
     * @throws Forbidden          when the write is not permitted
     * @throws NotFound           when no album has this id
     * @throws ValidationFailed   when the linkage fails validation
     * @throws ServerError        when the server fails
     * @throws TransportException when no response is received at all
     */
    public function remove(array $tracks): void
    {
        $this->fetcher->send(Request::delete(
            '/albums/' . $this->id . '/relationships/tracks',
            \json_encode(['data' => AlbumWrite::linkageList($tracks)], \JSON_THROW_ON_ERROR),
        ));
    }

    /**
     * Replace the whole relationship.
     *
     * @param list<Identifier|Track> $tracks
     *
     * @throws BadRequest         when the document is malformed
     * @throws Unauthorized       when credentials are missing or invalid
     * @throws Forbidden          when the write is not permitted
     * @throws NotFound           when no album has this id
     * @throws ValidationFailed   when the linkage fails validation
     * @throws ServerError        when the server fails
     * @throws TransportException when no response is received at all
     */
    public function replace(array $tracks): void
    {
        $this->fetcher->send(Request::patch(
            '/albums/' . $this->id . '/relationships/tracks',
            \json_encode(['data' => AlbumWrite::linkageList($tracks)], \JSON_THROW_ON_ERROR),
        ));
    }

    /**
     * @return PaginatedCollection<Identifier, PageNumber>
     */
    private function linkage(ResourceContext $context): PaginatedCollection
    {
        $document = $context->document();
        $identifiers = [];

        foreach ($context->primaryList() as $raw) {
            $identifiers[] = Identifier::fromArray($raw, 'the "tracks" linkage');
        }

        return new PaginatedCollection(
            $identifiers,
            PageNumber::read($document->pageMeta(), PageLinks::fromArray($document->links())),
            $document->meta(),
            $document->links(),
            fn(string $url): PaginatedCollection => $this->linkage($this->fetcher->readUrl($url)),
        );
    }

    /**
     * @return PaginatedCollection<Track, PageNumber>
     */
    private function resources(ResourceContext $context): PaginatedCollection
    {
        $document = $context->document();
        $tracks = [];

        foreach ($context->primaryList() as $raw) {
            $tracks[] = Track::_hydrate($raw, $context);
        }

        return new PaginatedCollection(
            $tracks,
            PageNumber::read($document->pageMeta(), PageLinks::fromArray($document->links())),
            $document->meta(),
            $document->links(),
            fn(string $url): PaginatedCollection => $this->resources($this->fetcher->readUrl($url, new ReadQuery())),
        );
    }
}
