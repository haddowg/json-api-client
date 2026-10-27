<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;

/**
 * `RESOURCE_IDENTIFIER_TYPE_INVALID`: Resource identifier type is invalid.
 *
 * A resource identifier in the request carries a `type` that is not a string. Send every `type` as
 * a JSON string.
 *
 * Example detail: The resource type must be a string instead of integer!
 *
 * @generated from `components.schemas.ResourceIdentifierTypeInvalidError` by bin/generate-error-codes.php
 */
final class ResourceIdentifierTypeInvalidError extends BadRequest implements CodedErrorResponse
{
    public const string CODE = 'RESOURCE_IDENTIFIER_TYPE_INVALID';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
