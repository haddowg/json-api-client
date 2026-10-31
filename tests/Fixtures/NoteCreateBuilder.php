<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

use haddowg\JsonApiClient\Exceptions\MissingRequiredMemberException;
use haddowg\JsonApiClient\Resources\ResourceContext;
use haddowg\JsonApiClient\Writes\CreateInput;

/**
 * Stands in for a generated fluent create builder.
 *
 * @implements CreateInput<Note>
 */
final class NoteCreateBuilder implements CreateInput
{
    private ?string $title = null;

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function build(): NoteCreate
    {
        if ($this->title === null) {
            throw MissingRequiredMemberException::for(Note::TYPE, ['title']);
        }

        return new NoteCreate($this->title);
    }

    public function toDocument(): array
    {
        return $this->build()->toDocument();
    }

    public function _hydrate(array $raw, ResourceContext $context): Note
    {
        return Note::_hydrate($raw, $context);
    }
}
