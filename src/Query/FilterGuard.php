<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Query;

use haddowg\JsonApiClient\Exceptions\UnknownFilterException;

/**
 * The runtime name check behind a filter a collection action takes.
 *
 * A generated action's `$filter` is typed by an array shape, which accepts an unknown optional key
 * just as a query's `filter()` door does, so the names are checked here too. A structured filter's
 * members are checked one level down, where the shape has the same blind spot.
 */
final class FilterGuard
{
    /**
     * @param array<string, mixed>        $filter  the filter values by name
     * @param list<string>                $names   the filters the endpoint accepts
     * @param array<string, list<string>> $members the members each structured filter's value accepts
     *
     * @return array<string, mixed> the filter, unchanged
     *
     * @throws UnknownFilterException when a name, or a structured filter's member, is not accepted
     */
    public static function check(array $filter, array $names, array $members = []): array
    {
        foreach ($filter as $name => $value) {
            $name = (string) $name;

            if (!\in_array($name, $names, true)) {
                throw UnknownFilterException::for($name, $names);
            }

            $accepted = $members[$name] ?? null;

            if ($accepted === null || !\is_array($value)) {
                continue;
            }

            $qualified = \array_map(static fn(string $member): string => $name . '[' . $member . ']', $accepted);

            foreach (\array_keys($value) as $member) {
                if (!\in_array((string) $member, $accepted, true)) {
                    throw UnknownFilterException::for($name . '[' . (string) $member . ']', $qualified);
                }
            }
        }

        return $filter;
    }
}
