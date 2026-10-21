<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

use haddowg\JsonApiClient\Support\ImmutableConditionable;
use haddowg\JsonApiClient\Support\Missing;

/**
 * Stands in for a generated update input, whose fluent setters return a new instance, so the
 * immutable conditional-composition trait is exercised through the shape codegen produces.
 */
final class AlbumUpdate
{
    use ImmutableConditionable;

    /** @phpstan-pure */
    public function __construct(
        public readonly string|Missing $title = Missing::Value,
        public readonly string|Missing $status = Missing::Value,
    ) {}

    /** @phpstan-pure */
    public function title(string $title): self
    {
        return new self(title: $title, status: $this->status);
    }

    /** @phpstan-pure */
    public function status(string $status): self
    {
        return new self(title: $this->title, status: $status);
    }
}
