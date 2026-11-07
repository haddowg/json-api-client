<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;

/**
 * `ATTRIBUTE_MEMBER_UNRECOGNIZED`: Attribute member is not recognized.
 *
 * The request body sent an attribute, or a key inside an object attribute, that the resource type
 * does not declare. Remove it, or correct its name, and send the request again.
 *
 * Example detail: The resource does not declare the member 'colour'!
 *
 * @generated from `components.schemas.AttributeMemberUnrecognizedError` by bin/generate-error-codes.php
 */
final class AttributeMemberUnrecognizedError extends BadRequest implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'ATTRIBUTE_MEMBER_UNRECOGNIZED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
