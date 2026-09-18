<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Exceptions\RelationNotIncludedException;
use haddowg\JsonApiClient\Resources\ResourceContext;
use haddowg\JsonApiClient\Support\Identifier;

/**
 * A `tracks` resource, trimmed to what the `albums` surface reaches.
 *
 * @generated from `components.schemas.TracksResource`
 */
final class Track extends TrackBase implements TrackHasAlbum
{
    public ?Album $album { get => $this->album(); }

    /**
     * @param array<string, mixed> $raw
     *
     * @throws MalformedDocument when the resource object is not a `tracks` one
     */
    public static function _hydrate(array $raw, ResourceContext $context): self
    {
        $identifier = Identifier::fromArray($raw, 'a "' . self::TYPE . '" resource object');

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
     * @throws RelationNotIncludedException when the read did not include `album` at this depth
     */
    public function album(): ?Album
    {
        $raw = $this->_includedOne('album', 'albumRef');

        return $raw === null ? null : Album::_hydrate($raw, $this->_child('album'));
    }
}
