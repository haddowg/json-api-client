<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;

/**
 * `RESOURCE_ID_INVALID`: Resource ID is invalid.
 *
 * The resource object's `id` is not a string, such as a number. Send `data.id` as a JSON string.
 *
 * @generated from `components.schemas.ResourceIdInvalidError` by bin/generate-error-codes.php
 */
final class ResourceIdInvalidError extends BadRequest implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'RESOURCE_ID_INVALID';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
