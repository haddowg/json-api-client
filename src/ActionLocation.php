<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient;

use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Support\Wire;

/**
 * The URL a custom action produced, such as a short-lived download link.
 *
 * An action declaring `SeeOther` with a media type answers a JSON:API request with a document
 * carrying the URL as its top-level `location` link. This is that link, read and never
 * followed: what to do with the URL is the caller's business.
 */
final readonly class ActionLocation
{
    /**
     * @param string                  $url       the link's `href`, as the server wrote it
     * @param string                  $type      the media type the URL serves
     * @param \DateTimeImmutable|null $expiresAt when the URL stops working, null when it does not expire
     */
    public function __construct(
        public string $url,
        public string $type,
        public ?\DateTimeImmutable $expiresAt = null,
    ) {}

    /**
     * Read the location off a decoded location document.
     *
     * The expiry comes from the link's `meta.expiresAt`, else the document's.
     *
     * @param array<string, mixed> $raw
     *
     * @throws MalformedDocument when the document has no `location` link with a string `href`
     *                           and `type`, or an expiry that is not a date
     */
    public static function fromDocument(array $raw): self
    {
        $link = Wire::map(Wire::map($raw, 'links'), 'location');
        $at = 'The location link';
        if ($link === []) {
            throw MalformedDocument::unexpectedMember('The response body', 'links', 'an object with a "location" link object');
        }

        $url = Wire::string($link, 'href') ?? throw MalformedDocument::missingMember($at, 'href');
        $type = Wire::string($link, 'type') ?? throw MalformedDocument::missingMember($at, 'type');
        $expiresAt = Wire::map($link, 'meta')['expiresAt'] ?? Wire::map($raw, 'meta')['expiresAt'] ?? null;

        return new self($url, $type, self::date($expiresAt));
    }

    /**
     * Whether a decoded document is a location document rather than one with primary data.
     *
     * @param array<string, mixed> $raw
     */
    public static function isLocationDocument(array $raw): bool
    {
        return !\array_key_exists('data', $raw) && Wire::map(Wire::map($raw, 'links'), 'location') !== [];
    }

    private static function date(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            throw MalformedDocument::unexpectedMember('The location link', 'expiresAt', 'a date-time string');
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw MalformedDocument::unexpectedMember('The location link', 'expiresAt', 'a date-time string');
        }
    }
}
