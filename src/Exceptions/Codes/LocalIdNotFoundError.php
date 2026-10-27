<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;

/**
 * `LOCAL_ID_NOT_FOUND`: Local id not found.
 *
 * An operation refers to a `lid` that no earlier operation in the batch created. Create the
 * resource under that `lid` in an earlier operation, or refer to an existing resource by `id`.
 *
 * Example detail: No resource is registered for local id 'new-article' of type 'articles'!
 *
 * @generated from `components.schemas.LocalIdNotFoundError` by bin/generate-error-codes.php
 */
final class LocalIdNotFoundError extends BadRequest implements CodedErrorResponse
{
    public const string CODE = 'LOCAL_ID_NOT_FOUND';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
