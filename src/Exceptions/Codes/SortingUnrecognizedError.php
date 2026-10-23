<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;

/**
 * `SORTING_UNRECOGNIZED`: Sorting parameter is unrecognized.
 *
 * The `sort` parameter names a field this endpoint cannot sort by. Sort only on the fields the API
 * documents for this resource type.
 *
 * Example detail: Sorting parameter 'popularity' can't be recognized by the endpoint!
 *
 * @generated from `components.schemas.SortingUnrecognizedError` by bin/generate-error-codes.php
 */
final class SortingUnrecognizedError extends BadRequest implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'SORTING_UNRECOGNIZED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
