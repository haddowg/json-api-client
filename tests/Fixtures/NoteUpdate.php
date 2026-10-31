<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

use haddowg\JsonApiClient\Resources\ResourceContext;
use haddowg\JsonApiClient\Writes\UpdateInput;

/**
 * Stands in for a generated update DTO.
 *
 * @implements UpdateInput<Note>
 */
final class NoteUpdate implements UpdateInput
{
    public function __construct(
        public readonly string $title,
        public readonly string $type = Note::TYPE,
    ) {}

    public function toDocument(string $id): array
    {
        return ['data' => ['type' => $this->type, 'id' => $id, 'attributes' => ['title' => $this->title]]];
    }

    public function _hydrate(array $raw, ResourceContext $context): Note
    {
        return Note::_hydrate($raw, $context);
    }
}
