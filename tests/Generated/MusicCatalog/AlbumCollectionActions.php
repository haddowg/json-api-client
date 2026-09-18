<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\Forbidden;
use haddowg\JsonApiClient\Exceptions\NotAcceptable;
use haddowg\JsonApiClient\Exceptions\ServerError;
use haddowg\JsonApiClient\Exceptions\TransportException;
use haddowg\JsonApiClient\Exceptions\Unauthorized;
use haddowg\JsonApiClient\Http\Request;
use haddowg\JsonApiClient\Resources\Fetcher;

/**
 * The collection-scoped custom actions of `albums`.
 *
 * Scope follows the path: `/albums/-actions/summary` carries no `{id}`, so it hangs off the type
 * accessor and not off a handle.
 *
 * @generated from `POST /albums/-actions/summary`
 */
final class AlbumCollectionActions
{
    public function __construct(private readonly Fetcher $fetcher) {}

    /**
     * Summarise the catalogue.
     *
     * No parameters, because the action declares no request body. The return is
     * `array<string, mixed>` and not a DTO, because the action responds with `MetaDocument`,
     * whose `meta` is the generic free-form `Meta` schema — there is no payload shape in the
     * document to generate one from.
     *
     * @return array<string, mixed>
     *
     * @throws BadRequest         when the request is malformed
     * @throws Unauthorized       when credentials are missing or invalid
     * @throws Forbidden          when the action is not permitted
     * @throws NotAcceptable      when the negotiated media type cannot be satisfied
     * @throws ServerError        when the server fails
     * @throws TransportException when no response is received at all
     */
    public function summary(): array
    {
        return $this->fetcher->meta(Request::post('/albums/-actions/summary'));
    }
}
