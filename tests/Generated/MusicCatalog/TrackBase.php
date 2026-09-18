<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Exceptions\FieldNotSelectedException;
use haddowg\JsonApiClient\Resources\Relationship;
use haddowg\JsonApiClient\Resources\ResourceObject;
use haddowg\JsonApiClient\Support\Identifier;

/**
 * A `tracks` resource with no relation hydrated.
 *
 * Trimmed to what the `albums` surface reaches. The `playlists` relation keeps its linkage
 * companions but has no hydrated accessor here, because that would pull in the `playlists`
 * type; a full run emits both.
 *
 * `previewOffset` is `format: time`, which coerces to `DateTimeImmutable` by the same rule as
 * `date` and `date-time`. A time with no date attaches itself to today, which is a real wart:
 * the value is a duration offset, and `DateTimeImmutable` is the wrong shape for it.
 *
 * @generated from `components.schemas.TracksResource`
 */
abstract class TrackBase extends ResourceObject
{
    public const string TYPE = 'tracks';

    public string $id { get => $this->id(); }

    public string $type { get => $this->type(); }

    public string $title { get => $this->title(); }

    public int $trackNumber { get => $this->trackNumber(); }

    public int $durationSeconds { get => $this->durationSeconds(); }

    public bool $explicit { get => $this->explicit(); }

    /** @var list<string> */
    public array $genres { get => $this->genres(); }

    public ?\DateTimeImmutable $previewOffset { get => $this->previewOffset(); }

    public string $displayTitle { get => $this->displayTitle(); }

    public function id(): string
    {
        return $this->_identifier()->id;
    }

    /**
     * @return self::TYPE
     */
    public function type(): string
    {
        return self::TYPE;
    }

    /**
     * @throws FieldNotSelectedException when a sparse fieldset excluded `title`
     */
    public function title(): string
    {
        return $this->_string('title');
    }

    /**
     * @throws FieldNotSelectedException when a sparse fieldset excluded `trackNumber`
     */
    public function trackNumber(): int
    {
        return $this->_int('trackNumber');
    }

    /**
     * @throws FieldNotSelectedException when a sparse fieldset excluded `durationSeconds`
     */
    public function durationSeconds(): int
    {
        return $this->_int('durationSeconds');
    }

    /**
     * @throws FieldNotSelectedException when a sparse fieldset excluded `explicit`
     */
    public function explicit(): bool
    {
        return $this->_bool('explicit');
    }

    /**
     * @return list<string>
     *
     * @throws FieldNotSelectedException when a sparse fieldset excluded `genres`
     */
    public function genres(): array
    {
        return $this->_stringList('genres');
    }

    /**
     * @throws FieldNotSelectedException when a sparse fieldset excluded `previewOffset`
     */
    public function previewOffset(): ?\DateTimeImmutable
    {
        return $this->_nullableDateTime('previewOffset');
    }

    /**
     * Read-only: present on the resource, absent from every write shape.
     *
     * @throws FieldNotSelectedException when a sparse fieldset excluded `displayTitle`
     */
    public function displayTitle(): string
    {
        return $this->_string('displayTitle');
    }

    public function albumRef(): ?Identifier
    {
        return $this->_relationship('album', true)->identifier();
    }

    public function hasAlbum(): bool
    {
        return !$this->_relationship('album', true)->isEmpty();
    }

    /**
     * @return list<Identifier>
     */
    public function playlistsRef(): array
    {
        return $this->_relationship('playlists', false)->identifiers();
    }

    public function hasPlaylists(): bool
    {
        return !$this->_relationship('playlists', false)->isEmpty();
    }

    /**
     * @param 'album'|'playlists' $name
     */
    public function _rel(string $name): Relationship
    {
        return $this->_relationship($name, $name === 'album');
    }
}
