<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

/**
 * Stands in for an enum generated from a schema's enumerated string values.
 */
enum AlbumStatus: string
{
    case Published = 'published';
    case Draft = 'draft';
}
