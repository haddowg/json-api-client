<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;

/**
 * `FIELDSET_MEMBER_UNRECOGNIZED`: Fieldset member is unrecognized.
 *
 * A `fields[TYPE]` parameter names a member that type does not have. Request only the attributes
 * and relationships the type declares.
 *
 * Example detail: Fields 'colour' requested for type 'articles' can't be recognized by the
 * endpoint!
 *
 * @generated from `components.schemas.FieldsetMemberUnrecognizedError` by bin/generate-error-codes.php
 */
final class FieldsetMemberUnrecognizedError extends BadRequest implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'FIELDSET_MEMBER_UNRECOGNIZED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
