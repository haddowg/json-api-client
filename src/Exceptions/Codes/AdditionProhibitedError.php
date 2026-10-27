<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\Forbidden;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;

/**
 * `ADDITION_PROHIBITED`: Addition is prohibited.
 *
 * This relationship does not let a client add members to it. Don't retry the request; the
 * relationship changes only in the ways the API documents for it.
 *
 * Example detail: Addition to relationship 'tags' is prohibited!
 *
 * @generated from `components.schemas.AdditionProhibitedError` by bin/generate-error-codes.php
 */
final class AdditionProhibitedError extends Forbidden implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'ADDITION_PROHIBITED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
