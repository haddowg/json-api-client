<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;

/**
 * `INCLUSION_UNSUPPORTED`: Inclusion is unsupported.
 *
 * This endpoint does not support the `include` parameter. Remove it and fetch related resources
 * with separate requests.
 *
 * Example detail: Inclusion is not supported by the endpoint!
 *
 * @generated from `components.schemas.InclusionUnsupportedError` by bin/generate-error-codes.php
 */
final class InclusionUnsupportedError extends BadRequest implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'INCLUSION_UNSUPPORTED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
