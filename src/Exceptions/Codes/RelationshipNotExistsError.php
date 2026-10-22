<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\NotFound;

/**
 * `RELATIONSHIP_NOT_EXISTS`: The requested relationship does not exist!.
 *
 * The request names a relationship this resource type does not have, or one whose endpoint the API
 * does not expose. Check the relationship name against the ones the type documents.
 *
 * @generated from `components.schemas.RelationshipNotExistsError` by bin/generate-error-codes.php
 */
final class RelationshipNotExistsError extends NotFound implements CodedErrorResponse
{
    public const string CODE = 'RELATIONSHIP_NOT_EXISTS';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
