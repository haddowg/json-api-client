<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;

/**
 * `FILTER_VALUE_INVALID`: Filter value is invalid.
 *
 * A `filter[...]` value fails the constraints that filter declares, for example text where a number
 * is expected. Correct the value of the parameter named in `source.parameter`; each failed
 * constraint is its own error.
 *
 * @generated from `components.schemas.FilterValueInvalidError` by bin/generate-error-codes.php
 */
final class FilterValueInvalidError extends BadRequest implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'FILTER_VALUE_INVALID';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
