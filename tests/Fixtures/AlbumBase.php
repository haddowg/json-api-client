<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

/**
 * Stands in for a generated resource DTO — the base a projection starts from.
 */
class AlbumBase
{
    public function __construct(public readonly string $id, public readonly string $title) {}
}
