<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\Forbidden;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;

/**
 * `FULL_REPLACEMENT_PROHIBITED`: Full replacement is prohibited.
 *
 * This relationship cannot be replaced as a whole, for example by a `PATCH` to its relationship
 * endpoint. Add or remove individual members instead, if the relationship allows that.
 *
 * @generated from `components.schemas.FullReplacementProhibitedError` by bin/generate-error-codes.php
 */
final class FullReplacementProhibitedError extends Forbidden implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'FULL_REPLACEMENT_PROHIBITED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
