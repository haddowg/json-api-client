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
 * Everything the `albums` collection endpoint offers.
 *
 * `create()` takes an id nowhere, because `AlbumsCreateRequest` declares `"id": false` —
 * client-generated ids are forbidden for this type, so the parameter is not absent by
 * convention, it is absent because the contract says so. A type declaring `clientId: optional`
 * gets `?string $id = null` and one declaring `required` gets it without a default.
 *
 * @phpstan-import-type AlbumCreateShape from AlbumShapes
 *
 * @generated from `GET /albums` and `POST /albums`
 */
final class Albums
{
    public function __construct(private readonly Fetcher $fetcher) {}

    /**
     * A read of the collection, unprojected and unfiltered.
     *
     * @return AlbumQuery<AlbumBase>
     */
    public function query(): AlbumQuery
    {
        /** @var AlbumQuery<AlbumBase> */
        return new AlbumQuery($this->fetcher);
    }

    /**
     * A handle on one album: reads, the update, the delete, the relationship writes and the
     * resource-scoped actions, none of which have been performed yet.
     */
    public function id(string $id): AlbumHandle
    {
        return new AlbumHandle($this->fetcher, $id);
    }

    /**
     * Create an album, in any of the three input forms.
     *
     * Always executes, always returns the resource. The optional projection shapes what comes
     * back and accepts a closure or a prebuilt {@see AlbumProjection} — the closure form reads
     * better inline, the object form is reusable across writes.
     *
     * The conditional return type is not decoration. A plain `@return AlbumBase&TProj` fails
     * with `Unable to resolve the template type TProj` when no projection is passed, because
     * there is then nothing to infer it from.
     *
     * @template TProj of object
     *
     * @param AlbumCreate|AlbumCreateBuilder|AlbumCreateShape                                    $input
     * @param (callable(AlbumProjection<AlbumBase>): AlbumProjection<TProj>)|AlbumProjection<TProj>|null $projection
     *
     * @return ($projection is null ? AlbumBase : AlbumBase&TProj)
     *
     * @throws BadRequest            when the request is malformed
     * @throws Unauthorized          when credentials are missing or invalid
     * @throws Forbidden             when the create is not permitted
     * @throws NotFound              when the endpoint does not exist
     * @throws NotAcceptable         when the negotiated media type cannot be satisfied
     * @throws Conflict              when the document conflicts with the endpoint
     * @throws UnsupportedMediaType  when the `Content-Type` is refused
     * @throws ValidationFailed      when the document fails validation
     * @throws ServerError           when the server fails
     * @throws TransportException    when no response is received at all
     * @throws MalformedDocument     when the response carries no primary resource
     */
    public function create(
        AlbumCreate|AlbumCreateBuilder|array $input,
        callable|AlbumProjection|null $projection = null,
    ): AlbumBase {
        $document = match (true) {
            $input instanceof AlbumCreate => $input,
            $input instanceof AlbumCreateBuilder => $input->build(),
            default => AlbumCreate::from($input),
        };

        $query = AlbumProjection::resolve($projection);

        $context = $this->fetcher->read(
            Request::post($query->appendTo('/albums'), \json_encode($document->toDocument(), \JSON_THROW_ON_ERROR)),
            $query,
        );

        $raw = $context->primary() ?? throw MalformedDocument::missingMember('The create response', 'data');

        /** @var AlbumBase&TProj */
        return $context->hydrate($raw, Album::_hydrate(...));
    }

    /**
     * Collection-scoped custom actions, behind a namespace because an action name can collide
     * with a relation method or a verb.
     */
    public function actions(): AlbumCollectionActions
    {
        return new AlbumCollectionActions($this->fetcher);
    }
}
