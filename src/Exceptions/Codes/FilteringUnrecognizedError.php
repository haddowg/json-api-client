<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;

/**
 * `FILTERING_UNRECOGNIZED`: Filtering parameter is unrecognized.
 *
 * The request filtered on a key this endpoint does not declare. Filter only on the keys the API
 * documents for this resource type.
 *
 * Example detail: Filtering parameter 'colour' can't be recognized by the endpoint!
 *
 * @generated from `components.schemas.FilteringUnrecognizedError` by bin/generate-error-codes.php
 */
final class FilteringUnrecognizedError extends BadRequest implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'FILTERING_UNRECOGNIZED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
