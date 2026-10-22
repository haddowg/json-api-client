<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\Forbidden;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;

/**
 * `CLIENT_GENERATED_ID_NOT_SUPPORTED`: Client generated ID is not supported.
 *
 * The create request supplied an `id`, but this resource type assigns its own ids. Leave `data.id`
 * out and the server generates one.
 *
 * @generated from `components.schemas.ClientGeneratedIdNotSupportedError` by bin/generate-error-codes.php
 */
final class ClientGeneratedIdNotSupportedError extends Forbidden implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'CLIENT_GENERATED_ID_NOT_SUPPORTED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
