<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;
use haddowg\JsonApiClient\Exceptions\ValidationFailed;

/**
 * `RESOURCE_ID_UNDECODABLE`: Resource ID is undecodable.
 *
 * The client-generated `id` is not in a format the server can decode into one of its own ids. Send
 * an `id` in the format the API documents for this resource type.
 *
 * @generated from `components.schemas.ResourceIdUndecodableError` by bin/generate-error-codes.php
 */
final class ResourceIdUndecodableError extends ValidationFailed implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'RESOURCE_ID_UNDECODABLE';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
