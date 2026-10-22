<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;

/**
 * `CURSOR_STALE`: Cursor is stale.
 *
 * The cursor was issued for a different sort order than this request asks for, so it no longer
 * marks a position in these results. Keep `sort` unchanged while paging, or start again from the
 * first page.
 *
 * @generated from `components.schemas.CursorStaleError` by bin/generate-error-codes.php
 */
final class CursorStaleError extends BadRequest implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'CURSOR_STALE';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
