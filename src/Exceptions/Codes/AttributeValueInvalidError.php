<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;
use haddowg\JsonApiClient\Exceptions\ValidationFailed;

/**
 * `ATTRIBUTE_VALUE_INVALID`: Attribute value is invalid.
 *
 * @generated from `components.schemas.AttributeValueInvalidError` by bin/generate-error-codes.php
 */
final class AttributeValueInvalidError extends ValidationFailed implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'ATTRIBUTE_VALUE_INVALID';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
