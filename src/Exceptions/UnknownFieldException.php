<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use haddowg\JsonApiClient\Support\DidYouMean;

/**
 * A sparse fieldset named a member the type does not have.
 *
 * Fieldsets sit in the same blind spot as filters: every token is optional, so
 * `fields(['albums' => ['titel']])` compiles, is sent, and comes back missing the field the
 * caller thought they asked for — which surfaces much later as a read that throws for a reason
 * unrelated to the real mistake.
 */
final class UnknownFieldException extends \RuntimeException implements JsonApiClientException
{
    private function __construct(
        string $message,
        public readonly string $resourceType,
        public readonly string $field,
    ) {
        parent::__construct($message);
    }

    /**
     * @param list<string> $known every attribute and relation the type exposes
     */
    public static function for(string $resourceType, string $field, array $known): self
    {
        return new self(
            \sprintf('"%s" is not a field of "%s".', $field, $resourceType)
            . DidYouMean::hint($field, $known),
            $resourceType,
            $field,
        );
    }
}
