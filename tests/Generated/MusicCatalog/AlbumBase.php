<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Exceptions\FieldNotSelectedException;
use haddowg\JsonApiClient\Resources\Relationship;
use haddowg\JsonApiClient\Resources\ResourceObject;
use haddowg\JsonApiClient\Support\Identifier;

/**
 * An `albums` resource with no relation hydrated: everything a read is guaranteed to give you.
 *
 * This is where a projection starts. `AlbumQuery<AlbumBase>` narrows to
 * `AlbumQuery<AlbumBase&AlbumHasArtist>` when `withArtist()` is called, so the relation
 * accessors are on the markers and deliberately not here — if they were, nothing would be
 * narrowed and `$album->tracks` would type-check on a read that never fetched it.
 *
 * Attributes *are* here, all of them, because a sparse fieldset is a wire concern. Reading one
 * the response did not carry throws rather than returning null: a null that means "excluded"
 * and a null that means "empty" are not the same answer, and only one of them is data.
 *
 * Values arrive coerced. `date-time`, `date` and `time` become `DateTimeImmutable`, `number`
 * becomes `float`, and the enumerated `status` becomes {@see AlbumStatus} — the same coercion
 * the server performs in the other direction, on the same wire form.
 *
 * @phpstan-import-type AlbumReleaseInfoShape from AlbumShapes
 *
 * @generated from `components.schemas.AlbumsResource`
 */
abstract class AlbumBase extends ResourceObject
{
    /**
     * The resource type, which is a constant for a generated class and not a wire read.
     */
    public const string TYPE = 'albums';

    public string $id { get => $this->id(); }

    public string $type { get => $this->type(); }

    public string $title { get => $this->title(); }

    public ?float $averageRating { get => $this->averageRating(); }

    public ?string $artwork { get => $this->artwork(); }

    public \DateTimeImmutable $releasedAt { get => $this->releasedAt(); }

    public bool $explicit { get => $this->explicit(); }

    public AlbumStatus $status { get => $this->status(); }

    public ?\DateTimeImmutable $availableFrom { get => $this->availableFrom(); }

    public ?\DateTimeImmutable $availableUntil { get => $this->availableUntil(); }

    /** @var AlbumReleaseInfoShape|null */
    public ?array $releaseInfo { get => $this->releaseInfo(); }

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
     * Read-only: present on the resource, absent from every write shape.
     *
     * @throws FieldNotSelectedException when a sparse fieldset excluded `averageRating`
     */
    public function averageRating(): ?float
    {
        return $this->_nullableFloat('averageRating');
    }

    /**
     * Read-only: present on the resource, absent from every write shape.
     *
     * @throws FieldNotSelectedException when a sparse fieldset excluded `artwork`
     */
    public function artwork(): ?string
    {
        return $this->_nullableString('artwork');
    }

    /**
     * @throws FieldNotSelectedException when a sparse fieldset excluded `releasedAt`
     */
    public function releasedAt(): \DateTimeImmutable
    {
        return $this->_dateTime('releasedAt');
    }

    /**
     * @throws FieldNotSelectedException when a sparse fieldset excluded `explicit`
     */
    public function explicit(): bool
    {
        return $this->_bool('explicit');
    }

    /**
     * @throws FieldNotSelectedException when a sparse fieldset excluded `status`
     */
    public function status(): AlbumStatus
    {
        return $this->_enum('status', AlbumStatus::class);
    }

    /**
     * @throws FieldNotSelectedException when a sparse fieldset excluded `availableFrom`
     */
    public function availableFrom(): ?\DateTimeImmutable
    {
        return $this->_nullableDateTime('availableFrom');
    }

    /**
     * @throws FieldNotSelectedException when a sparse fieldset excluded `availableUntil`
     */
    public function availableUntil(): ?\DateTimeImmutable
    {
        return $this->_nullableDateTime('availableUntil');
    }

    /**
     * A nested object attribute, narrowed member by member.
     *
     * The runtime hands back `array<string, mixed>` because it knows nothing about this schema;
     * the declared shape is recovered here with a guard per member rather than an assertion,
     * so a server that sends the wrong type for `label` drops the member instead of poisoning
     * the type.
     *
     * @return AlbumReleaseInfoShape|null
     *
     * @throws FieldNotSelectedException when a sparse fieldset excluded `releaseInfo`
     */
    public function releaseInfo(): ?array
    {
        $raw = $this->_nullableMap('releaseInfo');

        if ($raw === null) {
            return null;
        }

        $shape = [];
        $label = $raw['label'] ?? null;
        $catalogueNumber = $raw['catalogueNumber'] ?? null;

        if (\is_string($label)) {
            $shape['label'] = $label;
        }

        if (\is_string($catalogueNumber)) {
            $shape['catalogueNumber'] = $catalogueNumber;
        }

        return $shape;
    }

    /**
     * The `artist` linkage, without hydrating anything. Never throws.
     */
    public function artistRef(): ?Identifier
    {
        return $this->_relationship('artist', true)->identifier();
    }

    /**
     * Whether the album links to an artist at all. Never throws.
     */
    public function hasArtist(): bool
    {
        return !$this->_relationship('artist', true)->isEmpty();
    }

    /**
     * The `tracks` linkage, without hydrating anything. Never throws.
     *
     * @return list<Identifier>
     */
    public function tracksRef(): array
    {
        return $this->_relationship('tracks', false)->identifiers();
    }

    /**
     * Whether the album links to any track. Never throws.
     */
    public function hasTracks(): bool
    {
        return !$this->_relationship('tracks', false)->isEmpty();
    }

    /**
     * A relationship object: linkage, links, and the `meta.total` a `withCount` read leaves
     * behind.
     *
     * This is the door for a relation carrying no value. `withCount=tracks` counts without
     * including, so `$album->tracks` still throws and the count is read here — one concept
     * covering introspection, links-only relations and counts alike.
     *
     * @param 'artist'|'tracks' $name
     */
    public function _rel(string $name): Relationship
    {
        return $this->_relationship($name, $name === 'artist');
    }
}
