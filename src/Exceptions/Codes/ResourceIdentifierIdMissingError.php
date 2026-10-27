<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;

/**
 * `RESOURCE_IDENTIFIER_ID_MISSING`: An ID for the resource identifier is missing.
 *
 * A resource identifier in the request has neither an `id` nor a `lid`. Identify each related
 * resource by its `id`, or by a `lid` inside an Atomic Operations request.
 *
 * Example detail: An ID or local ID (lid) for the resource identifier must be included!
 *
 * @generated from `components.schemas.ResourceIdentifierIdMissingError` by bin/generate-error-codes.php
 */
final class ResourceIdentifierIdMissingError extends BadRequest implements CodedErrorResponse
{
    public const string CODE = 'RESOURCE_IDENTIFIER_ID_MISSING';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
