<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;

/**
 * `RELATIONSHIP_COUNT_NOT_ALLOWED`: Relationship count is not allowed.
 *
 * `withCount` names a relationship that is not a countable to-many relationship of this resource.
 * Ask for counts only on the relationships the API marks as countable.
 *
 * @generated from `components.schemas.RelationshipCountNotAllowedError` by bin/generate-error-codes.php
 */
final class RelationshipCountNotAllowedError extends BadRequest implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'RELATIONSHIP_COUNT_NOT_ALLOWED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
