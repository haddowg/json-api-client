<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;

/**
 * `RESOURCE_TYPE_MISSING`: Resource type is missing.
 *
 * The resource object in `data` has no `type`. Add the type of the resource the endpoint serves.
 *
 * @generated from `components.schemas.ResourceTypeMissingError` by bin/generate-error-codes.php
 */
final class ResourceTypeMissingError extends BadRequest implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'RESOURCE_TYPE_MISSING';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
