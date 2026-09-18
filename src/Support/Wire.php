<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Support;

/**
 * Typed reads over a decoded JSON payload.
 *
 * Every wire member is `mixed` until something proves otherwise, and the runtime's posture
 * is light structural guards rather than validation: a member of the wrong type reads as
 * absent instead of throwing. These helpers keep that decision in one place and out of the
 * value objects.
 *
 * @internal
 */
final class Wire
{
    /**
     * @param array<string, mixed> $raw
     */
    public static function string(array $raw, string $key): ?string
    {
        $value = $raw[$key] ?? null;

        return \is_string($value) ? $value : null;
    }

    /**
     * Read an integer, accepting the numeric string a JSON source may legitimately carry
     * (JSON:API mandates a string `status`, and page meta is not always emitted as a number).
     *
     * @param array<string, mixed> $raw
     */
    public static function int(array $raw, string $key): ?int
    {
        $value = $raw[$key] ?? null;

        if (\is_int($value)) {
            return $value;
        }

        return \is_string($value) && \preg_match('/^-?\d+$/', $value) === 1 ? (int) $value : null;
    }

    /**
     * A nested object, as a string-keyed map. A list or a scalar reads as absent.
     *
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    public static function map(array $raw, string $key): array
    {
        $value = $raw[$key] ?? null;

        if (!\is_array($value)) {
            return [];
        }

        /** @var array<string, mixed> $out */
        $out = [];
        foreach ($value as $k => $v) {
            $out[(string) $k] = $v;
        }

        return $out;
    }

    /**
     * A list of strings, skipping any member that is not one.
     *
     * @param array<string, mixed> $raw
     *
     * @return list<string>
     */
    public static function strings(array $raw, string $key): array
    {
        $value = $raw[$key] ?? null;

        if (!\is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $member) {
            if (\is_string($member)) {
                $out[] = $member;
            }
        }

        return $out;
    }

    /**
     * A JSON:API link value, which the spec allows as either a URL string or a link object
     * carrying `href`. Returns the URL in both cases.
     */
    public static function href(mixed $link): ?string
    {
        if (\is_string($link)) {
            return $link;
        }

        if (\is_array($link) && \is_string($link['href'] ?? null)) {
            /** @var string $href */
            $href = $link['href'];

            return $href;
        }

        return null;
    }

    /**
     * Decode a JSON body to a string-keyed map, or `null` when it is not a JSON object.
     *
     * @return array<string, mixed>|null
     */
    public static function decode(string $body): ?array
    {
        if (\trim($body) === '') {
            return null;
        }

        try {
            $decoded = \json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!\is_array($decoded)) {
            return null;
        }

        /** @var array<string, mixed> $out */
        $out = [];
        foreach ($decoded as $k => $v) {
            $out[(string) $k] = $v;
        }

        return $out;
    }
}
