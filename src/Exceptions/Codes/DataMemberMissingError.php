<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;

/**
 * `DATA_MEMBER_MISSING`: Missing `data` member at the document's top level.
 *
 * @generated from `components.schemas.DataMemberMissingError` by bin/generate-error-codes.php
 */
final class DataMemberMissingError extends BadRequest implements CodedErrorResponse
{
    public const string CODE = 'DATA_MEMBER_MISSING';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
