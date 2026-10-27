<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\Forbidden;

/**
 * `INSUFFICIENT_SCOPE`: Insufficient scope.
 *
 * The access token is valid but does not grant every scope the request needs. `meta.missingScopes`
 * names the ones it lacks; request a token that grants them, or drop the `include` path named in
 * `meta.include`.
 *
 * Example detail: The access token does not grant every scope this request needs.
 *
 * @generated from `components.schemas.InsufficientScopeError` by bin/generate-error-codes.php
 */
final class InsufficientScopeError extends Forbidden implements CodedErrorResponse
{
    public const string CODE = 'INSUFFICIENT_SCOPE';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
