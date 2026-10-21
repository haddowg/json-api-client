<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Support;

use haddowg\JsonApiClient\Resources\ResourceObject;

/**
 * Write-side coercions every generated write input shares.
 *
 * The array door accepts the loose wire form (an ISO string for a date, a backing value for
 * an enum) where the typed doors take the native value. These turn the loose form into the
 * native one and pass {@see Missing} and `null` through untouched, so absence and an explicit
 * clear stay distinct all the way to the request document.
 */
final class Coerce
{
    /**
     * A `format: date-time` or `format: date` input.
     */
    public static function dateTime(\DateTimeImmutable|Missing|string $value): \DateTimeImmutable|Missing
    {
        return \is_string($value) ? new \DateTimeImmutable($value) : $value;
    }

    /**
     * A nullable date input. Null is a value here, not an absence: it clears the member, where
     * {@see Missing} leaves it alone.
     */
    public static function nullableDateTime(\DateTimeImmutable|Missing|string|null $value): \DateTimeImmutable|Missing|null
    {
        return \is_string($value) ? new \DateTimeImmutable($value) : $value;
    }

    /**
     * An enumerated input, accepting the backing value the loose door allows.
     *
     * @template T of \BackedEnum
     *
     * @param class-string<T> $enum
     * @param T|Missing|int|string $value
     *
     * @return T|Missing
     */
    public static function enum(string $enum, \BackedEnum|Missing|int|string $value): \BackedEnum|Missing
    {
        return \is_string($value) || \is_int($value) ? $enum::from($value) : $value;
    }

    /**
     * To-one linkage. A resource in hand, a bare reference, or nothing.
     *
     * An identifier's linkage `meta` is kept, since it is how a relation carries pivot data on a
     * write (`meta.pivot`). An empty one is left off.
     *
     * @return array{type: string, id: string, meta?: array<string, mixed>}|null
     */
    public static function linkage(Identifier|ResourceObject|null $target): ?array
    {
        if ($target === null) {
            return null;
        }

        $identifier = $target instanceof ResourceObject ? $target->_identifier() : $target;
        $linkage = ['type' => $identifier->type, 'id' => $identifier->id];

        return $identifier->meta === [] ? $linkage : [...$linkage, 'meta' => $identifier->meta];
    }

    /**
     * To-many linkage, in the order given.
     *
     * @param list<Identifier|ResourceObject> $targets
     *
     * @return list<array{type: string, id: string, meta?: array<string, mixed>}>
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
