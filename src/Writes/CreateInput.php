<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Writes;

use haddowg\JsonApiClient\Exceptions\MissingRequiredMemberException;
use haddowg\JsonApiClient\Resources\ResourceContext;

/**
 * A generated create input: the document it sends, and how to read the resource the server
 * answers with.
 *
 * Every generated `<Type>Create` implements it, and so does every `<Type>CreateBuilder`, which
 * builds the DTO to produce its document. An atomic batch spans types, and only the input knows
 * which type it writes, so this is what lets one runtime recorder take every type's create.
 *
 * @template-covariant T of object
 */
interface CreateInput
{
    /**
     * The create request document.
     *
     * @return array<string, mixed>
     *
     * @throws MissingRequiredMemberException when a builder is missing a member the create requires
     */
    public function toDocument(): array;

    /**
     * Materialise the resource a write of this input answered with.
     *
     * @param array<string, mixed> $raw
     *
     * @return T
     */
    public function _hydrate(array $raw, ResourceContext $context): object;
}
