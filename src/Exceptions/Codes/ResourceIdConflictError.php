<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\Conflict;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;

/**
 * `RESOURCE_ID_CONFLICT`: Resource id conflict.
 *
 * @generated from `components.schemas.ResourceIdConflictError` by bin/generate-error-codes.php
 */
final class ResourceIdConflictError extends Conflict implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'RESOURCE_ID_CONFLICT';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
