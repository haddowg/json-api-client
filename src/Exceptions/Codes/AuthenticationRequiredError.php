<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\Unauthorized;

/**
 * `AUTHENTICATION_REQUIRED`: Unauthorized.
 *
 * The request carried no credentials, or credentials the server rejected, such as an expired token.
 * Authenticate, then send the request again with valid credentials.
 *
 * @generated from `components.schemas.AuthenticationRequiredError` by bin/generate-error-codes.php
 */
final class AuthenticationRequiredError extends Unauthorized implements CodedErrorResponse
{
    public const string CODE = 'AUTHENTICATION_REQUIRED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
