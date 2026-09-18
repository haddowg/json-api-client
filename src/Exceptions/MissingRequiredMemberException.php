<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

/**
 * A fluent write builder was built without a member the create document requires.
 *
 * The builder's one measured blind spot: PHP cannot statically require that a setter was
 * called. The named-argument DTO catches this at compile time, which is why the docs lead with
 * it; the builder is for conditional assembly and pays for it here.
 */
final class MissingRequiredMemberException extends \RuntimeException implements JsonApiClientException
{
    /**
     * @param list<string> $members
     */
    private function __construct(
        string $message,
        public readonly string $resourceType,
        public readonly array $members,
    ) {
        parent::__construct($message);
    }

    /**
     * @param list<string> $members every required member still unset, in declaration order
     */
    public static function for(string $resourceType, array $members): self
    {
        return new self(
            \sprintf(
                'Cannot build a "%s" write input: %s %s required and %s not been set.',
                $resourceType,
                \implode(', ', $members),
                \count($members) === 1 ? 'is' : 'are',
                \count($members) === 1 ? 'has' : 'have',
            ),
            $resourceType,
            $members,
        );
    }
}
