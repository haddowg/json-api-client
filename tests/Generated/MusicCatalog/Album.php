<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Collections\ResourceCollection;
use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Exceptions\RelationNotIncludedException;
use haddowg\JsonApiClient\Resources\ResourceContext;
use haddowg\JsonApiClient\Support\Identifier;

/**
 * An `albums` resource as it actually arrives: every attribute, and every relation accessor.
 *
 * The runtime value is always this class — the projection is a *static* narrowing over it, so
 * `$album->tracks` exists at runtime whether or not it type-checks, and calling it without the
 * include throws. That asymmetry is the design: static checking where it is cheap, a loud
 * runtime backstop everywhere else.
 *
 * The write entry points are static factories here rather than on the client accessor. Both
 * arities return an inert value, so neither performs IO and a forgotten `create()` call cannot
 * silently no-op — and one import reaches every write form for the type.
 *
 * @phpstan-import-type AlbumCreateShape from AlbumShapes
 * @phpstan-import-type AlbumUpdateShape from AlbumShapes
 *
 * @generated from `components.schemas.AlbumsResource`
 */
final class Album extends AlbumBase implements AlbumHasArtist, AlbumHasTracks
{
    public ?Artist $artist { get => $this->artist(); }

    /** @var ResourceCollection<Track> */
    public ResourceCollection $tracks { get => $this->tracks(); }

    /**
     * Build a resource from one resource object of an indexed response.
     *
     * @param array<string, mixed> $raw
     *
     * @throws MalformedDocument when the resource object is not an `albums` one
     */
    public static function _hydrate(array $raw, ResourceContext $context): self
    {
        $identifier = Identifier::fromArray($raw, 'an "' . self::TYPE . '" resource object');

        if ($identifier->type !== self::TYPE) {
            throw MalformedDocument::unexpectedMember(
                'The resource object',
                'type',
                '"' . self::TYPE . '" (got "' . $identifier->type . '")',
            );
        }

        return new self($identifier, $raw, $context);
    }

    /**
     * A resource identifier for an album that is not in hand, for a relationship write.
     *
     * @param array<string, mixed> $meta
     */
    public static function ref(string $id, array $meta = []): Identifier
    {
        return Identifier::of(self::TYPE, $id, $meta);
    }

    /**
     * A reusable projection for a write response: `include` and `fields`, and nothing a `POST`
     * would be rejected for carrying.
     *
     * @return AlbumProjection<AlbumBase>
     */
    public static function projection(): AlbumProjection
    {
        return AlbumProjection::make();
    }

    /**
     * The create input, as a builder or as the canonical DTO.
     *
     * Both arities are expressible in one signature. Passing nothing hands back a builder for
     * conditional assembly; passing an array validates its keys against the descriptor and
     * hands back the DTO the builder would have produced.
     *
     * @param AlbumCreateShape|null $input
     *
     * @return ($input is null ? AlbumCreateBuilder : AlbumCreate)
     *
     * @throws \haddowg\JsonApiClient\Exceptions\UnknownAttributeException when the array names a member `albums` does not have
     */
    public static function create(?array $input = null): AlbumCreateBuilder|AlbumCreate
    {
        return $input === null ? new AlbumCreateBuilder() : AlbumCreate::from($input);
    }

    /**
     * The update input, as a builder or as the canonical DTO.
     *
     * @param AlbumUpdateShape|null $input
     *
     * @return ($input is null ? AlbumUpdateBuilder : AlbumUpdate)
     *
     * @throws \haddowg\JsonApiClient\Exceptions\UnknownAttributeException when the array names a member `albums` does not have
     */
    public static function update(?array $input = null): AlbumUpdateBuilder|AlbumUpdate
    {
        return $input === null ? new AlbumUpdateBuilder() : AlbumUpdate::from($input);
    }

    /**
     * @throws RelationNotIncludedException when the read did not include `artist`
     */
    public function artist(): ?Artist
    {
        $raw = $this->_includedOne('artist', 'artistRef');

        return $raw === null ? null : Artist::_hydrate($raw, $this->_child('artist'));
    }

    /**
     * @return ResourceCollection<Track>
     *
     * @throws RelationNotIncludedException when the read did not include `tracks`
     */
    public function tracks(): ResourceCollection
    {
        $context = $this->_child('tracks');
        $tracks = [];

        foreach ($this->_includedMany('tracks', 'tracksRef') as $raw) {
            $tracks[] = Track::_hydrate($raw, $context);
        }

        return new ResourceCollection($tracks);
    }
}
