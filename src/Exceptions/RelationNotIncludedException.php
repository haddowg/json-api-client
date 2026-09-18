<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

/**
 * A relation was read that the response never hydrated.
 *
 * The loud floor under the static narrowing. Depth 1 is a compile error; deeper, and after a
 * conditional or dynamic include, this is what you get instead of a silent null. The message
 * carries the include path and the always-safe companion, because at the moment it fires the
 * caller wants one of the two.
 */
final class RelationNotIncludedException extends \RuntimeException implements JsonApiClientException
{
    private function __construct(
        string $message,
        public readonly string $resourceType,
        public readonly string $relation,
        public readonly string $includePath,
    ) {
        parent::__construct($message);
    }

    /**
     * @param string $includePath the path that would have hydrated it, at the depth it sits
     * @param string $companion   the linkage-only accessor that never throws
     */
    public static function for(
        string $resourceType,
        string $relation,
        string $includePath,
        string $companion,
    ): self {
        return new self(
            \sprintf(
                'The "%s" relation of "%s" was not included in this response. '
                . 'Ask for it with ->with(\'%s\'), or read its linkage with ->%s(), which never throws.',
                $relation,
                $resourceType,
                $includePath,
                $companion,
            ),
            $resourceType,
            $relation,
            $includePath,
        );
    }
}
