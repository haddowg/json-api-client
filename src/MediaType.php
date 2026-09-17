<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient;

/**
 * The JSON:API media type and the parameters the client negotiates it with.
 *
 * Content negotiation belongs to the runtime rather than the generated code, so the
 * media type is built here and reused for both `Content-Type` and `Accept`.
 */
final class MediaType
{
    public const string JSON_API = 'application/vnd.api+json';

    /**
     * The extension URI for the JSON:API Atomic Operations extension.
     */
    public const string ATOMIC_OPERATIONS = 'https://jsonapi.org/ext/atomic';

    /**
     * Build the media type value carrying the extensions and profiles a request applies.
     *
     * Each parameter is emitted once with its URIs space-separated, as JSON:API 1.1
     * requires.
     *
     * @param list<string> $extensions extension URIs
     * @param list<string> $profiles   profile URIs
     */
    public static function value(array $extensions = [], array $profiles = []): string
    {
        $value = self::JSON_API;

        if ($extensions !== []) {
            $value .= '; ext="' . \implode(' ', $extensions) . '"';
        }

        if ($profiles !== []) {
            $value .= '; profile="' . \implode(' ', $profiles) . '"';
        }

        return $value;
    }
}
