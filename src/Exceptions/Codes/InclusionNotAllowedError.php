<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;

/**
 * `INCLUSION_NOT_ALLOWED`: Inclusion is not allowed.
 *
 * An `include` path names a relationship that exists but may not be included from this request.
 * Remove the path and fetch the related resources with a separate request.
 *
 * @generated from `components.schemas.InclusionNotAllowedError` by bin/generate-error-codes.php
 */
final class InclusionNotAllowedError extends BadRequest implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'INCLUSION_NOT_ALLOWED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
