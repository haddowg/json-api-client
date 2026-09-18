<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\Forbidden;
use haddowg\JsonApiClient\Exceptions\NotFound;
use haddowg\JsonApiClient\Exceptions\ServerError;
use haddowg\JsonApiClient\Exceptions\TransportException;
use haddowg\JsonApiClient\Exceptions\Unauthorized;
use haddowg\JsonApiClient\Exceptions\ValidationFailed;
use haddowg\JsonApiClient\Http\Request;
use haddowg\JsonApiClient\Resources\Fetcher;
use haddowg\JsonApiClient\Support\Identifier;

/**
 * The `artist` relationship of one album.
 *
 * A to-one, and the generated verbs say so: `PATCH /albums/{id}/relationships/artist` is
 * declared and `POST`/`DELETE` are not, so `set()` exists and `add()` does not. Calling
 * `add()` is an undefined method rather than a runtime 404 from code that compiled.
 *
 * Both reads exist because both endpoints do. `related()` is `/albums/{id}/artist` and `get()`
 * is `/albums/{id}/relationships/artist`; the two suppression flags are independent, so a
 * server that switched either off would have that method absent and the other still present.
 *
 * @generated from `/albums/{id}/artist` and `/albums/{id}/relationships/artist`
 */
final class AlbumArtistRelation
{
    public function __construct(
        private readonly Fetcher $fetcher,
        private readonly string $id,
    ) {}

    /**
     * The linkage alone: `{type, id}` or nothing, without fetching the artist.
     *
     * @throws BadRequest         when a query parameter is rejected
     * @throws Unauthorized       when credentials are missing or invalid
     * @throws Forbidden          when the read is not permitted
     * @throws NotFound           when no album has this id
     * @throws ServerError        when the server fails
     * @throws TransportException when no response is received at all
     */
    public function get(): ?Identifier
    {
        $raw = $this->fetcher
            ->read(Request::get('/albums/' . $this->id . '/relationships/artist'))
            ->primary();

        return $raw === null ? null : Identifier::fromArray($raw, 'the "artist" linkage');
    }

    /**
     * The artist itself.
     *
     * @throws BadRequest         when a query parameter is rejected
     * @throws Unauthorized       when credentials are missing or invalid
     * @throws Forbidden          when the read is not permitted
     * @throws NotFound           when no album has this id
     * @throws ServerError        when the server fails
     * @throws TransportException when no response is received at all
     */
    public function related(): ?Artist
    {
        $context = $this->fetcher->read(Request::get('/albums/' . $this->id . '/artist'));
        $raw = $context->primary();

        return $raw === null ? null : Artist::_hydrate($raw, $context);
    }

    /**
     * Point the relationship at an artist, or at nothing.
     *
     * @throws BadRequest         when the document is malformed
     * @throws Unauthorized       when credentials are missing or invalid
     * @throws Forbidden          when the write is not permitted
     * @throws NotFound           when no album has this id
     * @throws ValidationFailed   when the linkage fails validation
     * @throws ServerError        when the server fails
     * @throws TransportException when no response is received at all
     */
    public function set(Artist|Identifier|null $target): void
    {
        $this->fetcher->send(Request::patch(
            '/albums/' . $this->id . '/relationships/artist',
            \json_encode(['data' => AlbumWrite::linkage($target)], \JSON_THROW_ON_ERROR),
        ));
    }
}
