<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

/**
 * An attribute was read that the response did not carry.
 *
 * Attributes are statically present on every resource because a sparse fieldset is a wire
 * concern, so this is where the wire gets its say. A fieldset that excluded the field says so;
 * a response that simply omitted it says that instead, which is the difference between a
 * caller's own mistake and a server the client cannot account for.
 */
final class FieldNotSelectedException extends \RuntimeException implements JsonApiClientException
{
    private function __construct(
        string $message,
        public readonly string $resourceType,
        public readonly string $field,
    ) {
        parent::__construct($message);
    }

    /**
     * @param list<string>|null $fieldset the sparse fieldset the request sent for this type, or
     *                                    null when it sent none
     */
    public static function for(string $resourceType, string $field, ?array $fieldset = null): self
    {
        $message = $fieldset === null
            ? \sprintf(
                'The "%s" field of "%s" is absent from this response, so there is no value to read.',
                $field,
                $resourceType,
            )
            : \sprintf(
                'The "%s" field of "%s" was excluded by the sparse fieldset fields[%s]=%s. '
                . 'Add it to the fieldset, or drop the fieldset.',
                $field,
                $resourceType,
                $resourceType,
                \implode(',', $fieldset),
            );

        return new self($message, $resourceType, $field);
    }
}
