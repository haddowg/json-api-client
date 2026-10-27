<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;

/**
 * `CURSOR_MALFORMED`: Cursor is malformed.
 *
 * The cursor in the named `page` parameter could not be decoded. Use cursors exactly as the server
 * returned them in a previous page's `links`; never build or edit one.
 *
 * Example detail: The cursor supplied in 'page[after]' could not be decoded.
 *
 * @generated from `components.schemas.CursorMalformedError` by bin/generate-error-codes.php
 */
final class CursorMalformedError extends BadRequest implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'CURSOR_MALFORMED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
