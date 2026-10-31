<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

use haddowg\JsonApiClient\Query\RelatedQuery;

/**
 * Stands in for a generated Relationship Query: the typed doors codegen emits over the runtime's.
 */
final class TrackRelatedQuery extends RelatedQuery
{
    public function whereExplicit(bool $value): static
    {
        return $this->filtering(['explicit' => $value]);
    }

    public function sort(string ...$tokens): static
    {
        return $this->sorting(\array_values($tokens));
    }
}
