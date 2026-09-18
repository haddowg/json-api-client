<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Query\Projection;
use haddowg\JsonApiClient\Query\ReadQuery;

/**
 * What an `albums` write response should bring back: `include` and `fields`, and nothing else.
 *
 * Projection is extracted from the query builder rather than reused because a write response
 * honours exactly these two parameters. Under the server's strict query validation a stray
 * `sort` on a `POST` is a `400`, not something quietly ignored — so `sort()`, `filter()` and
 * `page()` are not merely unused here, they are undefined methods.
 *
 * Reusable: build one and hand it to every write that should come back the same shape.
 *
 * @template TProj of object
 *
 * @extends Projection<TProj>
 *
 * @generated from the `include` and `fields` parameters of `GET /albums/{id}`
 */
final class AlbumProjection extends Projection
{
    /** @use AlbumProjecting<TProj> */
    use AlbumProjecting;

    /**
     * An empty projection over the unnarrowed resource.
     *
     * @return self<AlbumBase>
     */
    public static function make(): self
    {
        /** @var self<AlbumBase> */
        return new self();
    }

    /**
     * Resolve either projection form to the query parameters a write response carries.
     *
     * Writes accept a closure or a prebuilt projection, and both end up here so every write
     * entry point shares one resolution rather than repeating it.
     *
     * @template T of object
     *
     * @param (callable(self<AlbumBase>): self<T>)|self<T>|null $projection
     */
    public static function resolve(callable|self|null $projection): ReadQuery
    {
        if ($projection === null) {
            return new ReadQuery();
        }

        return ($projection instanceof self ? $projection : $projection(self::make()))->toReadQuery();
    }
}
