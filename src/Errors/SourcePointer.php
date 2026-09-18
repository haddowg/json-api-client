<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Errors;

/**
 * Inverts a write error's `source.pointer` back to the input path the caller supplied.
 *
 * The client owns the JSON:API envelope, so it knows the inverse mapping the server cannot:
 * a caller who passed `title` gets a `422` pointing at `/data/attributes/title`, and grouping
 * validation errors for a form needs `title` back. Reserved segments use the leading
 * underscore the rest of the client reserves (`_pivot`), which a JSON:API member name can
 * never start with.
 *
 * ```
 * /data/attributes/title                                          => title
 * /data/attributes/releaseInfo/label                              => releaseInfo.label
 * /data/id                                                        => id
 * /data/relationships/artist/data                                 => artist
 * /data/relationships/orderedTracks/data/0/meta/pivot/position    => orderedTracks[0]._pivot.position
 * /atomic:operations/2/data/attributes/title                      => title, at op index 2
 * ```
 *
 * A pointer this does not recognise is returned verbatim rather than mangled — the raw
 * pointer is always still on the error as the escape hatch. Query-side errors carry
 * `source.parameter` instead and never reach here.
 */
final class SourcePointer
{
    /**
     * The reserved member the client surfaces a linkage `meta.pivot` block under.
     */
    public const string PIVOT = '_pivot';

    private const string ATOMIC_ROOT = 'atomic:operations';

    /**
     * Remap a resource-document pointer to the caller's input path.
     */
    public static function toPath(string $pointer): string
    {
        $segments = self::segments($pointer);

        if (($segments[0] ?? null) !== 'data') {
            return $pointer;
        }

        $attribute = \implode('.', \array_slice($segments, 2));

        return match ($segments[1] ?? null) {
            // A bare `/data/attributes` names no member, so there is nothing to remap to.
            'attributes' => $attribute === '' ? $pointer : $attribute,
            'id' => 'id',
            'relationships' => self::relationshipPath(\array_slice($segments, 2)),
            default => $pointer,
        };
    }

    /**
     * Remap a relationship-endpoint pointer, where the relation name comes from the route
     * rather than the document.
     *
     * Two pointer shapes arrive at `/{type}/{id}/relationships/{rel}`: the linkage document
     * rooted at `data` (`/data/0/meta/pivot/position`), and the resource-document shape the
     * server's relationship prohibitions emit unchanged (`/data/relationships/{rel}`). Both
     * resolve under the route's relation.
     */
    public static function toRelationshipPath(string $relation, string $pointer): string
    {
        $segments = self::segments($pointer);

        if (($segments[0] ?? null) !== 'data') {
            return $pointer;
        }

        $tail = ($segments[1] ?? null) === 'relationships'
            ? \array_slice($segments, 3)
            : \array_slice($segments, 1);

        return self::relationshipPath([$relation, ...$tail]);
    }

    /**
     * Split an atomic pointer into its operation index and the path within that operation.
     *
     * An atomic `422` points at `/atomic:operations/{n}/data/...`, so the prefix carries the
     * failing operation and the tail is remapped exactly as a standalone write's would be.
     * A pointer without the prefix yields a null index and is remapped verbatim.
     *
     * @return array{opIndex: int|null, path: string}
     */
    public static function toAtomicPath(string $pointer): array
    {
        $segments = self::segments($pointer);

        if (($segments[0] ?? null) !== self::ATOMIC_ROOT) {
            return ['opIndex' => null, 'path' => self::toPath($pointer)];
        }

        $index = $segments[1] ?? null;
        if ($index === null || \preg_match('/^\d+$/', $index) !== 1) {
            return ['opIndex' => null, 'path' => $pointer];
        }

        $tail = '/' . \implode('/', \array_slice($segments, 2));

        return ['opIndex' => (int) $index, 'path' => self::toPath($tail)];
    }

    /**
     * Resolve the tail of a relationship pointer, whose leading segment is the relation name.
     *
     * A `data` segment is structural and dropped, a numeric segment becomes an index, and a
     * `meta/pivot` pair collapses to the reserved `_pivot` member — pivot values nest under a
     * linkage member's `meta.pivot`, symmetric with how they are read.
     *
     * @param list<string> $segments
     */
    private static function relationshipPath(array $segments): string
    {
        $name = $segments[0] ?? null;
        if ($name === null) {
            return 'data/relationships';
        }

        $path = $name;
        $count = \count($segments);

        for ($i = 1; $i < $count; $i++) {
            $segment = $segments[$i];

            if ($segment === 'data') {
                continue;
            }

            if (\preg_match('/^\d+$/', $segment) === 1) {
                $path .= '[' . $segment . ']';

                continue;
            }

            if ($segment === 'meta' && ($segments[$i + 1] ?? null) === 'pivot') {
                $path .= '.' . self::PIVOT;
                $i++;

                continue;
            }

            $path .= '.' . $segment;
        }

        return $path;
    }

    /**
     * @return list<string>
     */
    private static function segments(string $pointer): array
    {
        return \array_values(\array_filter(\explode('/', $pointer), static fn(string $s): bool => $s !== ''));
    }
}
