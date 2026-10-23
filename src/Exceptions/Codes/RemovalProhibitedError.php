<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\Forbidden;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;

/**
 * `REMOVAL_PROHIBITED`: Removal is prohibited.
 *
 * This relationship does not let a client remove members from it or clear it. Don't retry the
 * request; the relationship changes only in the ways the API documents for it.
 *
 * @generated from `components.schemas.RemovalProhibitedError` by bin/generate-error-codes.php
 */
final class RemovalProhibitedError extends Forbidden implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'REMOVAL_PROHIBITED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
