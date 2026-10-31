<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

use haddowg\JsonApiClient\Atomic\Created;
use haddowg\JsonApiClient\Resources\ResourceContext;
use haddowg\JsonApiClient\Support\Coerce;
use haddowg\JsonApiClient\Support\Identifier;
use haddowg\JsonApiClient\Writes\CreateInput;

/**
 * Stands in for a generated create DTO, with an optional client-generated id and a to-one
 * relation back to another note.
 *
 * @implements CreateInput<Note>
 */
final class NoteCreate implements CreateInput
{
    /**
     * @param Created<Note>|Identifier|Note|null $parent
     */
    public function __construct(
        public readonly string $title,
        public readonly ?string $id = null,
        public readonly Created|Identifier|Note|null $parent = null,
    ) {}

    public function toDocument(): array
    {
        $data = ['type' => Note::TYPE];

        if ($this->id !== null) {
            $data['id'] = $this->id;
        }

        $data['attributes'] = ['title' => $this->title];

        if ($this->parent !== null) {
            $data['relationships'] = ['parent' => ['data' => Coerce::linkage($this->parent)]];
        }

        return ['data' => $data];
    }

    public function _hydrate(array $raw, ResourceContext $context): Note
    {
        return Note::_hydrate($raw, $context);
    }
}
