<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

use haddowg\JsonApiClient\Resources\ResourceContext;
use haddowg\JsonApiClient\Resources\ResourceObject;
use haddowg\JsonApiClient\Support\Identifier;

/**
 * A minimal resource class, standing in for a generated one where a test needs a write to read
 * its result back.
 */
final class Note extends ResourceObject
{
    public const string TYPE = 'notes';

    /**
     * @param array<string, mixed> $raw
     */
    public static function _hydrate(array $raw, ResourceContext $context): self
    {
        return new self(Identifier::fromArray($raw, 'a "notes" resource object'), $raw, $context);
    }

    public function title(): string
    {
        return $this->_string('title');
    }
}
