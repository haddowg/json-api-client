<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;

/**
 * `RESOURCE_IDENTIFIER_TYPE_MISSING`: A type for the resource identifier is missing.
 *
 * A resource identifier in the request has no `type`. Add the related resource's `type` to every
 * identifier.
 *
 * Example detail: A type for the resource identifier must be included!
 *
 * @generated from `components.schemas.ResourceIdentifierTypeMissingError` by bin/generate-error-codes.php
 */
final class ResourceIdentifierTypeMissingError extends BadRequest implements CodedErrorResponse
{
    public const string CODE = 'RESOURCE_IDENTIFIER_TYPE_MISSING';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
