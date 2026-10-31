<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

use haddowg\JsonApiClient\ClientOptions;

/**
 * Stands in for a generated root client, whose constructor takes the options a bridge builds.
 */
final class StubClient
{
    public function __construct(public readonly ClientOptions $options) {}
}
