<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;

/**
 * `QUERY_PARAM_MALFORMED`: Query parameter is malformed.
 *
 * A query parameter has the wrong shape, such as `fields` sent without a type key or `include` sent
 * as a list instead of a comma-separated string. Fix the parameter named in `source.parameter`.
 *
 * @generated from `components.schemas.QueryParamMalformedError` by bin/generate-error-codes.php
 */
final class QueryParamMalformedError extends BadRequest implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'QUERY_PARAM_MALFORMED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
