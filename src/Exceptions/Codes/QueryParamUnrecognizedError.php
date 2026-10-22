<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;

/**
 * `QUERY_PARAM_UNRECOGNIZED`: Query parameter is unrecognized.
 *
 * The request used a query parameter this endpoint does not recognize. Remove it, or check its
 * spelling against the parameters the API documents.
 *
 * @generated from `components.schemas.QueryParamUnrecognizedError` by bin/generate-error-codes.php
 */
final class QueryParamUnrecognizedError extends BadRequest implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'QUERY_PARAM_UNRECOGNIZED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
