<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;

/**
 * `REQUIRED_TOP_LEVEL_MEMBERS_MISSING`: Required top-level members are missing.
 *
 * @generated from `components.schemas.RequiredTopLevelMembersMissingError` by bin/generate-error-codes.php
 */
final class RequiredTopLevelMembersMissingError extends BadRequest implements CodedErrorResponse
{
    public const string CODE = 'REQUIRED_TOP_LEVEL_MEMBERS_MISSING';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
