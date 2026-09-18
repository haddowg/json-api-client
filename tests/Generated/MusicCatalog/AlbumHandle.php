<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\Conflict;
use haddowg\JsonApiClient\Exceptions\Forbidden;
use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Exceptions\NotAcceptable;
use haddowg\JsonApiClient\Exceptions\NotFound;
use haddowg\JsonApiClient\Exceptions\ServerError;
use haddowg\JsonApiClient\Exceptions\TransportException;
use haddowg\JsonApiClient\Exceptions\Unauthorized;
use haddowg\JsonApiClient\Exceptions\UnsupportedMediaType;
use haddowg\JsonApiClient\Exceptions\ValidationFailed;
use haddowg\JsonApiClient\Http\Request;
use haddowg\JsonApiClient\Resources\Fetcher;

/**
 * One album by id, before anything has been fetched.
 *
 * An inert handle, which is what makes it safe to hang the relationship writes and the
 * resource-scoped actions off it: `$client->albums->id('1')` performs no IO, and every method
 * on it is an explicit call.
 *
 * `get()` takes a projection rather than a query builder, and that follows the document rather
 * than a preference: `GET /albums/{id}` advertises `include` and `fields` and no other query
 * parameter, which is exactly what {@see AlbumProjection} is.
 *
 * @phpstan-import-type AlbumUpdateShape from AlbumShapes
 *
 * @generated from `GET`, `PATCH` and `DELETE /albums/{id}`
 */
final class AlbumHandle
{
    public function __construct(
        private readonly Fetcher $fetcher,
        private readonly string $id,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    /**
     * Fetch the album.
     *
     * @template TProj of object
     *
     * @param (callable(AlbumProjection<AlbumBase>): AlbumProjection<TProj>)|AlbumProjection<TProj>|null $projection
     *
     * @return ($projection is null ? AlbumBase : AlbumBase&TProj)
     *
     * @throws BadRequest         when a query parameter is rejected
     * @throws Unauthorized       when credentials are missing or invalid
     * @throws Forbidden          when the read is not permitted
     * @throws NotFound           when no album has this id
     * @throws NotAcceptable      when the negotiated media type cannot be satisfied
     * @throws ServerError        when the server fails
     * @throws TransportException when no response is received at all
     * @throws MalformedDocument  when the response carries no primary resource
     */
    public function get(callable|AlbumProjection|null $projection = null): AlbumBase
    {
        $query = AlbumProjection::resolve($projection);
        $context = $this->fetcher->read(Request::get($query->appendTo('/albums/' . $this->id)), $query);
        $raw = $context->primary() ?? throw MalformedDocument::missingMember('The albums response', 'data');

        /** @var AlbumBase&TProj */
        return $context->hydrate($raw, Album::_hydrate(...));
    }

    /**
     * Patch the album, in any of the three input forms.
     *
     * The id comes from the handle and is written into the document, so the body and the URL
     * cannot disagree.
     *
     * @template TProj of object
     *
     * @param AlbumUpdate|AlbumUpdateBuilder|AlbumUpdateShape                                    $input
     * @param (callable(AlbumProjection<AlbumBase>): AlbumProjection<TProj>)|AlbumProjection<TProj>|null $projection
     *
     * @return ($projection is null ? AlbumBase : AlbumBase&TProj)
     *
     * @throws BadRequest           when the request is malformed
     * @throws Unauthorized         when credentials are missing or invalid
     * @throws Forbidden            when the update is not permitted
     * @throws NotFound             when no album has this id
     * @throws NotAcceptable        when the negotiated media type cannot be satisfied
     * @throws Conflict             when the document's type or id disagrees with the endpoint
     * @throws UnsupportedMediaType when the `Content-Type` is refused
     * @throws ValidationFailed     when the document fails validation
     * @throws ServerError          when the server fails
     * @throws TransportException   when no response is received at all
     * @throws MalformedDocument    when the response carries no primary resource
     */
    public function update(
        AlbumUpdate|AlbumUpdateBuilder|array $input,
        callable|AlbumProjection|null $projection = null,
    ): AlbumBase {
        $document = match (true) {
            $input instanceof AlbumUpdate => $input,
            $input instanceof AlbumUpdateBuilder => $input->build(),
            default => AlbumUpdate::from($input),
        };

        $query = AlbumProjection::resolve($projection);

        $context = $this->fetcher->read(
            Request::patch(
                $query->appendTo('/albums/' . $this->id),
                \json_encode($document->toDocument($this->id), \JSON_THROW_ON_ERROR),
            ),
            $query,
        );

        $raw = $context->primary() ?? throw MalformedDocument::missingMember('The update response', 'data');

        /** @var AlbumBase&TProj */
        return $context->hydrate($raw, Album::_hydrate(...));
    }

    /**
     * @throws Unauthorized       when credentials are missing or invalid
     * @throws Forbidden          when the delete is not permitted
     * @throws NotFound           when no album has this id
     * @throws ServerError        when the server fails
     * @throws TransportException when no response is received at all
     */
    public function delete(): void
    {
        $this->fetcher->send(Request::delete('/albums/' . $this->id));
    }

    /**
     * The `artist` relationship: a to-one, so it has `set()` and no `add()`.
     */
    public function artist(): AlbumArtistRelation
    {
        return new AlbumArtistRelation($this->fetcher, $this->id);
    }

    /**
     * The `tracks` relationship: a to-many whose endpoint declares `POST`, `DELETE` and `PATCH`,
     * so all three verbs are generated.
     */
    public function tracks(): AlbumTracksRelation
    {
        return new AlbumTracksRelation($this->fetcher, $this->id);
    }

    /**
     * Resource-scoped custom actions.
     */
    public function actions(): AlbumActions
    {
        return new AlbumActions($this->fetcher, $this->id);
    }
}
