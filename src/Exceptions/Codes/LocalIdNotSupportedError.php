<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;

/**
 * `LOCAL_ID_NOT_SUPPORTED`: Local id is not supported.
 *
 * The request used a `lid`, which only means something inside an Atomic Operations request.
 * Identify the resource by `id`, or send the dependent writes together as one atomic batch if the
 * API offers the extension.
 *
 * Example detail: A local id (lid) is only supported within an Atomic Operations request; a
 * standalone request must identify a resource by id.
 *
 * @generated from `components.schemas.LocalIdNotSupportedError` by bin/generate-error-codes.php
 */
final class LocalIdNotSupportedError extends BadRequest implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'LOCAL_ID_NOT_SUPPORTED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
