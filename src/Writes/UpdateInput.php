<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Writes;

use haddowg\JsonApiClient\Resources\ResourceContext;

/**
 * A generated update input: the document it sends for a given id, and how to read the resource
 * the server answers with.
 *
 * Every generated `<Type>Update` implements it, for the same reason as {@see CreateInput}.
 *
 * @template-covariant T of object
 */
interface UpdateInput
{
    /**
     * The update request document for the resource with this id.
     *
     * @return array<string, mixed>
     */
    public function toDocument(string $id): array;

    /**
     * Materialise the resource a write of this input answered with.
     *
     * @param array<string, mixed> $raw
     *
     * @return T
     */
    public function _hydrate(array $raw, ResourceContext $context): object;
}
