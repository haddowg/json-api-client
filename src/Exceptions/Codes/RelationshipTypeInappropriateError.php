<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;

/**
 * `RELATIONSHIP_TYPE_INAPPROPRIATE`: Relationship type is inappropriate.
 *
 * @generated from `components.schemas.RelationshipTypeInappropriateError` by bin/generate-error-codes.php
 */
final class RelationshipTypeInappropriateError extends BadRequest implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'RELATIONSHIP_TYPE_INAPPROPRIATE';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
