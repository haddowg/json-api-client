<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;

/**
 * `PAGINATION_KIND_UNKNOWN`: Pagination kind is not supported.
 *
 * `page[kind]` names a pagination strategy this collection does not offer. Use one of the
 * strategies the `detail` lists, or leave `page[kind]` out.
 *
 * Example detail: The pagination strategy 'page[kind]=offset' is not supported; use one of: page,
 * cursor.
 *
 * @generated from `components.schemas.PaginationKindUnknownError` by bin/generate-error-codes.php
 */
final class PaginationKindUnknownError extends BadRequest implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'PAGINATION_KIND_UNKNOWN';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
