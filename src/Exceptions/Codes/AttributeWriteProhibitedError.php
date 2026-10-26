<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\Forbidden;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;

/**
 * `ATTRIBUTE_WRITE_PROHIBITED`: Attribute write is prohibited.
 *
 * The request body sent an attribute the client cannot write in this request, such as a read-only
 * or computed one. Remove the member from `data.attributes` and send the request again.
 *
 * Example detail: Attribute 'createdAt' cannot be written in this request!
 *
 * @generated from `components.schemas.AttributeWriteProhibitedError` by bin/generate-error-codes.php
 */
final class AttributeWriteProhibitedError extends Forbidden implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'ATTRIBUTE_WRITE_PROHIBITED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
