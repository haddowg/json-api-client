<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\Conflict;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;

/**
 * `RESOURCE_TYPE_UNACCEPTABLE`: Resource type is unacceptable.
 *
 * @generated from `components.schemas.ResourceTypeUnacceptableError` by bin/generate-error-codes.php
 */
final class ResourceTypeUnacceptableError extends Conflict implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'RESOURCE_TYPE_UNACCEPTABLE';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
