<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use haddowg\JsonApiClient\Support\DidYouMean;

/**
 * A write input named a member the type does not have.
 *
 * This guards the one blind spot the measurements found: an all-optional array shape accepts
 * any unknown key, so `['titel' => 'x']` type-checks, sends nothing, and quietly no-ops. That
 * is a data-loss bug, and it is the reason the generated `from()` exists at all rather than the
 * array being passed straight through.
 */
final class UnknownAttributeException extends \RuntimeException implements JsonApiClientException
{
    private function __construct(
        string $message,
        public readonly string $resourceType,
        public readonly string $member,
    ) {
        parent::__construct($message);
    }

    /**
     * @param list<string> $known every attribute and relation the input accepts
     */
    public static function for(string $resourceType, string $member, array $known): self
    {
        return new self(
            \sprintf('"%s" is not a writable member of "%s".', $member, $resourceType)
            . DidYouMean::hint($member, $known),
            $resourceType,
            $member,
        );
    }
}
