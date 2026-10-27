<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;

/**
 * `RESOURCE_IDENTIFIER_ID_INVALID`: Resource identifier ID is invalid.
 *
 * A resource identifier in the request carries an `id` that is not a string, such as a number. Send
 * every `id` as a JSON string.
 *
 * Example detail: The resource ID must be a string instead of integer!
 *
 * @generated from `components.schemas.ResourceIdentifierIdInvalidError` by bin/generate-error-codes.php
 */
final class ResourceIdentifierIdInvalidError extends BadRequest implements CodedErrorResponse
{
    public const string CODE = 'RESOURCE_IDENTIFIER_ID_INVALID';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
