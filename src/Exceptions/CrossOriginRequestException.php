<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

/**
 * A request would leave the base URL's origin, so it was not sent.
 *
 * The client sends its configured headers, credentials included, on every request. A URL the
 * server hands back, such as a pagination link, is followed only when its scheme, host and port
 * match the base URL's. Anything else is refused before the headers are resolved, rather than
 * followed with or without them.
 */
final class CrossOriginRequestException extends \RuntimeException implements JsonApiClientException
{
    public static function for(string $url, string $baseUrl): self
    {
        return new self(\sprintf(
            'Refusing to request %s: only URLs on the origin of the base URL %s are followed, because every request carries the client\'s headers.',
            \json_encode($url, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE),
            $baseUrl,
        ));
    }
}
