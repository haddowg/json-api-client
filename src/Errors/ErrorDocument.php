<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Errors;

use haddowg\JsonApiClient\Support\Wire;

/**
 * Reads the `errors` member of a JSON:API error document.
 *
 * Deliberately forgiving: a gateway that returns HTML on a 502, or a proxy that truncates the
 * body, still has to produce an exception carrying the status. A body this cannot parse
 * yields no errors rather than a second failure on top of the first.
 */
final class ErrorDocument
{
    /**
     * @return list<Error>
     */
    public static function parse(string $body): array
    {
        $decoded = Wire::decode($body);

        if ($decoded === null) {
            return [];
        }

        return self::fromArray($decoded);
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return list<Error>
     */
    public static function fromArray(array $document): array
    {
        $errors = $document['errors'] ?? null;

        if (!\is_array($errors)) {
            return [];
        }

        $out = [];
        foreach ($errors as $error) {
            if (!\is_array($error)) {
                continue;
            }

            /** @var array<string, mixed> $member */
            $member = [];
            foreach ($error as $k => $v) {
                $member[(string) $k] = $v;
            }

            $out[] = Error::fromArray($member);
        }

        return $out;
    }
}
