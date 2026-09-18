<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

/**
 * The response is not a shape the runtime can work with.
 *
 * This is the light structural guard, not validation: it fires when the invariant the
 * materialiser rests on is violated (a body that is not a JSON object, a linkage member with
 * no `type`), never for a field whose value is the wrong kind. Per-field validation is opt-in
 * and throws its own engine's errors.
 */
final class MalformedDocument extends \RuntimeException implements JsonApiClientException
{
    public static function notAnObject(string $at = 'The response body'): self
    {
        return new self($at . ' is not a JSON:API document: expected a JSON object.');
    }

    public static function missingMember(string $at, string $member): self
    {
        return new self(\sprintf('%s is missing a string "%s" member.', $at, $member));
    }

    public static function unexpectedMember(string $at, string $member, string $expected): self
    {
        return new self(\sprintf('%s has a "%s" member that is not %s.', $at, $member, $expected));
    }
}
