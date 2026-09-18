<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Collections\ResourceCollection;
use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Exceptions\RelationNotIncludedException;
use haddowg\JsonApiClient\Resources\ResourceContext;
use haddowg\JsonApiClient\Support\Identifier;

/**
 * An `artists` resource, trimmed to what the `albums` surface reaches.
 *
 * @generated from `components.schemas.ArtistsResource`
 */
final class Artist extends ArtistBase implements ArtistHasAlbums
{
    /** @var ResourceCollection<Album> */
    public ResourceCollection $albums { get => $this->albums(); }

    /**
     * @param array<string, mixed> $raw
     *
     * @throws MalformedDocument when the resource object is not an `artists` one
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
     * @param array<string, mixed> $meta
     */
    public static function ref(string $id, array $meta = []): Identifier
    {
        return Identifier::of(self::TYPE, $id, $meta);
    }

    /**
     * @return ResourceCollection<Album>
     *
     * @throws RelationNotIncludedException when the read did not include `albums` at this depth
     */
    public function albums(): ResourceCollection
    {
        $context = $this->_child('albums');
        $albums = [];

        foreach ($this->_includedMany('albums', 'albumsRef') as $raw) {
            $albums[] = Album::_hydrate($raw, $context);
        }

        return new ResourceCollection($albums);
    }
}
