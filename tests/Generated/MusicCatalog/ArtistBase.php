<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Exceptions\FieldNotSelectedException;
use haddowg\JsonApiClient\Resources\Relationship;
use haddowg\JsonApiClient\Resources\ResourceObject;
use haddowg\JsonApiClient\Support\Identifier;

/**
 * An `artists` resource with no relation hydrated.
 *
 * Trimmed to what the `albums` surface reaches: the attributes and the `albums` relation. A
 * full run emits the query builder, write shapes, accessor and handle for this type too.
 *
 * @generated from `components.schemas.ArtistsResource`
 */
abstract class ArtistBase extends ResourceObject
{
    public const string TYPE = 'artists';

    public string $id { get => $this->id(); }

    public string $type { get => $this->type(); }

    public string $name { get => $this->name(); }

    public string $slug { get => $this->slug(); }

    public ?string $website { get => $this->website(); }

    public ?string $bio { get => $this->bio(); }

    public int $trackCount { get => $this->trackCount(); }

    public \DateTimeImmutable $createdAt { get => $this->createdAt(); }

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
     * @throws FieldNotSelectedException when a sparse fieldset excluded `name`
     */
    public function name(): string
    {
        return $this->_string('name');
    }

    /**
     * @throws FieldNotSelectedException when a sparse fieldset excluded `slug`
     */
    public function slug(): string
    {
        return $this->_string('slug');
    }

    /**
     * @throws FieldNotSelectedException when a sparse fieldset excluded `website`
     */
    public function website(): ?string
    {
        return $this->_nullableString('website');
    }

    /**
     * @throws FieldNotSelectedException when a sparse fieldset excluded `bio`
     */
    public function bio(): ?string
    {
        return $this->_nullableString('bio');
    }

    /**
     * @throws FieldNotSelectedException when a sparse fieldset excluded `trackCount`
     */
    public function trackCount(): int
    {
        return $this->_int('trackCount');
    }

    /**
     * @throws FieldNotSelectedException when a sparse fieldset excluded `createdAt`
     */
    public function createdAt(): \DateTimeImmutable
    {
        return $this->_dateTime('createdAt');
    }

    /**
     * @return list<Identifier>
     */
    public function albumsRef(): array
    {
        return $this->_relationship('albums', false)->identifiers();
    }

    public function hasAlbums(): bool
    {
        return !$this->_relationship('albums', false)->isEmpty();
    }

    /**
     * @param 'albums' $name
     */
    public function _rel(string $name): Relationship
    {
        return $this->_relationship($name, false);
    }
}
