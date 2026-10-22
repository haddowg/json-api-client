<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;

/**
 * `RESOURCE_IDENTIFIER_LID_INVALID`: Resource identifier local ID is invalid.
 *
 * A resource identifier in the request carries a `lid` that is not a string. Send every `lid` as a
 * JSON string.
 *
 * @generated from `components.schemas.ResourceIdentifierLidInvalidError` by bin/generate-error-codes.php
 */
final class ResourceIdentifierLidInvalidError extends BadRequest implements CodedErrorResponse
{
    public const string CODE = 'RESOURCE_IDENTIFIER_LID_INVALID';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
