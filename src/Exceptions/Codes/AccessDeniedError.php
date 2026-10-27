<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\Forbidden;

/**
 * `ACCESS_DENIED`: Forbidden.
 *
 * The caller is authenticated but not permitted to do what the request asks. Retrying with the same
 * credentials will fail the same way; act as a user who holds the permission, or don't make the
 * request.
 *
 * Example detail: Access is denied!
 *
 * @generated from `components.schemas.AccessDeniedError` by bin/generate-error-codes.php
 */
final class AccessDeniedError extends Forbidden implements CodedErrorResponse
{
    public const string CODE = 'ACCESS_DENIED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
