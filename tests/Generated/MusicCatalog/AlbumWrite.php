<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Resources\ResourceObject;
use haddowg\JsonApiClient\Support\Identifier;
use haddowg\JsonApiClient\Support\Missing;

/**
 * Coercions the `albums` write DTOs share.
 *
 * The array door is the loose one by design, so it accepts what a caller is likely to have:
 * an ISO string where the typed signature wants `DateTimeImmutable`, the enum's backing value
 * where it wants the enum. Everything lands as the canonical type before the DTO is built, so
 * there is still one serialisation path regardless of which door was used.
 *
 * Emitted once per type rather than inlined into both DTOs: create and update share every
 * coercion, and two copies of a date parser is two places for a format to drift.
 *
 * @generated from `components.schemas.AlbumsCreateAttributes` and `components.schemas.AlbumsUpdateAttributes`
 */
final class AlbumWrite
{
    /**
     * A `format: date-time` input, accepting the ISO string the loose door allows.
     */
    public static function dateTime(\DateTimeImmutable|Missing|string $value): \DateTimeImmutable|Missing
    {
        return \is_string($value) ? new \DateTimeImmutable($value) : $value;
    }

    /**
     * A nullable `format: date` input. Null is a value here, not an absence: it clears the
     * member, where {@see Missing} leaves it alone.
     */
    public static function nullableDate(\DateTimeImmutable|Missing|string|null $value): \DateTimeImmutable|Missing|null
    {
        return \is_string($value) ? new \DateTimeImmutable($value) : $value;
    }

    /**
     * An enumerated input, accepting the backing value the loose door allows.
     */
    public static function status(AlbumStatus|Missing|string $value): AlbumStatus|Missing
    {
        return \is_string($value) ? AlbumStatus::from($value) : $value;
    }

    /**
     * To-one linkage. A resource in hand, a bare reference, or nothing.
     *
     * @return array{type: string, id: string}|null
     */
    public static function linkage(Identifier|ResourceObject|null $target): ?array
    {
        if ($target === null) {
            return null;
        }

        $identifier = $target instanceof ResourceObject ? $target->_identifier() : $target;

        return ['type' => $identifier->type, 'id' => $identifier->id];
    }

    /**
     * To-many linkage, in the order given.
     *
     * @param list<Identifier|ResourceObject> $targets
     *
     * @return list<array{type: string, id: string}>
     */
    public static function linkageList(array $targets): array
    {
        $out = [];

        foreach ($targets as $target) {
            $out[] = self::linkage($target) ?? throw new \LogicException('A to-many member cannot be null.');
        }

        return $out;
    }
}
