<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\NotFound;

/**
 * `RESOURCE_NOT_FOUND`: Resource not found.
 *
 * @generated from `components.schemas.ResourceNotFoundError` by bin/generate-error-codes.php
 */
final class ResourceNotFoundError extends NotFound implements CodedErrorResponse
{
    public const string CODE = 'RESOURCE_NOT_FOUND';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
