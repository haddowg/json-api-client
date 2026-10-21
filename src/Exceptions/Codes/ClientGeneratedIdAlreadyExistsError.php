<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\Conflict;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;

/**
 * `CLIENT_GENERATED_ID_ALREADY_EXISTS`: Client generated ID already exists.
 *
 * @generated from `components.schemas.ClientGeneratedIdAlreadyExistsError` by bin/generate-error-codes.php
 */
final class ClientGeneratedIdAlreadyExistsError extends Conflict implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'CLIENT_GENERATED_ID_ALREADY_EXISTS';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
