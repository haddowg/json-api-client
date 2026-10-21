<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;
use haddowg\JsonApiClient\Exceptions\ValidationFailed;

/**
 * `RELATED_ATTRIBUTE_OWNER_MISSING`: Related attribute owner missing.
 *
 * @generated from `components.schemas.RelatedAttributeOwnerMissingError` by bin/generate-error-codes.php
 */
final class RelatedAttributeOwnerMissingError extends ValidationFailed implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'RELATED_ATTRIBUTE_OWNER_MISSING';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
