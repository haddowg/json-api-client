<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Exceptions\BadRequest;
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
use Psr\Http\Message\StreamInterface;

/**
 * The resource-scoped custom actions of `albums`.
 *
 * Behind a namespace rather than on the handle directly, because an action name is free to
 * collide with a relation method or a verb — `$album->tracks()` could be either without it.
 *
 * Each signature is derived from the action's own declaration, and these two are deliberately
 * different in kind. `reissue` declares `AlbumsCreateRequest` as its body and `AlbumsDocument`
 * as its response, so it takes the three write forms and returns a resource. `artwork` declares
 * `application/octet-stream` and a `204`, so it takes a raw body and returns nothing — and it
 * takes a *nullable* one, because its request body is `required: false`.
 *
 * @phpstan-import-type AlbumCreateShape from AlbumShapes
 *
 * @generated from `POST /albums/{id}/-actions/reissue` and `POST /albums/{id}/-actions/artwork`
 */
final class AlbumActions
{
    public function __construct(
        private readonly Fetcher $fetcher,
        private readonly string $id,
    ) {}

    /**
     * Reissue the album.
     *
     * @template TProj of object
     *
     * @param AlbumCreate|AlbumCreateBuilder|AlbumCreateShape                                    $input
     * @param (callable(AlbumProjection<AlbumBase>): AlbumProjection<TProj>)|AlbumProjection<TProj>|null $projection
     *
     * @return ($projection is null ? AlbumBase : AlbumBase&TProj)
     *
     * @throws BadRequest           when the request is malformed
     * @throws Unauthorized         when credentials are missing or invalid
     * @throws Forbidden            when the action is not permitted
     * @throws NotFound             when no album has this id
     * @throws NotAcceptable        when the negotiated media type cannot be satisfied
     * @throws UnsupportedMediaType when the `Content-Type` is refused
     * @throws ValidationFailed     when the document fails validation
     * @throws ServerError          when the server fails
     * @throws TransportException   when no response is received at all
     * @throws MalformedDocument    when the response carries no primary resource
     */
    public function reissue(
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
            Request::post(
                $query->appendTo('/albums/' . $this->id . '/-actions/reissue'),
                \json_encode($document->toDocument(), \JSON_THROW_ON_ERROR),
            ),
            $query,
        );

        $raw = $context->primary() ?? throw MalformedDocument::missingMember('The reissue response', 'data');

        /** @var AlbumBase&TProj */
        return $context->hydrate($raw, Album::_hydrate(...));
    }

    /**
     * Upload artwork.
     *
     * The action relaxes content-type negotiation and owns the body shape, so the body goes over
     * the wire verbatim under `application/octet-stream` while the response is still negotiated
     * as JSON:API.
     *
     * @throws BadRequest           when the request is malformed
     * @throws Unauthorized         when credentials are missing or invalid
     * @throws Forbidden            when the action is not permitted
     * @throws NotFound             when no album has this id
     * @throws UnsupportedMediaType when the `Content-Type` is refused
     * @throws ValidationFailed     when the payload fails validation
     * @throws ServerError          when the server fails
     * @throws TransportException   when no response is received at all
     */
    public function artwork(StreamInterface|string|null $body = null): void
    {
        $request = Request::post('/albums/' . $this->id . '/-actions/artwork');

        $this->fetcher->send(
            $body === null ? $request : $request->withRawBody($body, 'application/octet-stream'),
        );
    }
}
