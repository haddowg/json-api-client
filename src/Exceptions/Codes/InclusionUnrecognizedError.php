<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;

/**
 * `INCLUSION_UNRECOGNIZED`: Inclusion is unrecognized.
 *
 * An `include` path names a relationship the resource type does not have. Check each path against
 * the relationships the type documents.
 *
 * Example detail: Included paths 'auther' can't be recognized by the endpoint!
 *
 * @generated from `components.schemas.InclusionUnrecognizedError` by bin/generate-error-codes.php
 */
final class InclusionUnrecognizedError extends BadRequest implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'INCLUSION_UNRECOGNIZED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
