<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;

/**
 * `LOCAL_ID_CONFLICT`: Local id conflict.
 *
 * @generated from `components.schemas.LocalIdConflictError` by bin/generate-error-codes.php
 */
final class LocalIdConflictError extends BadRequest implements CodedErrorResponse
{
    public const string CODE = 'LOCAL_ID_CONFLICT';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
