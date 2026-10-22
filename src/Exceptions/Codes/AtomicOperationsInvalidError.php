<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;

/**
 * `ATOMIC_OPERATIONS_INVALID`: Atomic operations request is invalid.
 *
 * An operation in the Atomic Operations request is malformed: a missing or unknown `op`, or a
 * broken `ref`, `href` or `data`. The server applied none of the batch; fix the operation at
 * `source.pointer` and send the whole batch again.
 *
 * Example detail: Unknown atomic operation code 'upsert'.
 *
 * @generated from `components.schemas.AtomicOperationsInvalidError` by bin/generate-error-codes.php
 */
final class AtomicOperationsInvalidError extends BadRequest implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'ATOMIC_OPERATIONS_INVALID';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
