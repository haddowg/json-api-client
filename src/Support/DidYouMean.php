<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Support;

/**
 * Turns a rejected name into a sentence a caller can act on.
 *
 * Every runtime guard in this client exists to backstop a static gap, and a guard that only
 * says "unknown key" wastes the moment it fires. The blind spots are all typos on optional
 * members, so the nearest known name is nearly always the answer.
 *
 * @internal
 */
final class DidYouMean
{
    /**
     * The closest known name, or null when nothing is close enough to be worth suggesting.
     *
     * The threshold scales with the length of what was typed, so `q` does not get told it
     * probably meant `title`.
     *
     * @param list<string> $known
     */
    public static function closest(string $given, array $known): ?string
    {
        $limit = (int) \max(2, \floor(\strlen($given) / 3) + 1);
        $best = null;
        $bestDistance = $limit + 1;

        foreach ($known as $candidate) {
            $distance = \levenshtein(\strtolower($given), \strtolower($candidate));

            if ($distance < $bestDistance) {
                $best = $candidate;
                $bestDistance = $distance;
            }
        }

        return $bestDistance <= $limit ? $best : null;
    }

    /**
     * The trailing half of a guard's message: a suggestion when there is one, the full list
     * of what is accepted otherwise.
     *
     * @param list<string> $known
     */
    public static function hint(string $given, array $known): string
    {
        if ($known === []) {
            return '';
        }

        $closest = self::closest($given, $known);

        if ($closest !== null) {
            return ' Did you mean "' . $closest . '"?';
        }

        return ' Known names are: ' . \implode(', ', $known) . '.';
    }
}
