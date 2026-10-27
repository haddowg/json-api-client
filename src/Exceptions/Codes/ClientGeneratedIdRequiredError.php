<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\Forbidden;
use haddowg\JsonApiClient\Exceptions\PointsAtDocumentMembers;

/**
 * `CLIENT_GENERATED_ID_REQUIRED`: Required client generated ID.
 *
 * This resource type expects the client to choose the id, and the create request supplied none. Add
 * `data.id` in the format the API documents and send the request again.
 *
 * Example detail: A client generated ID must be used!
 *
 * @generated from `components.schemas.ClientGeneratedIdRequiredError` by bin/generate-error-codes.php
 */
final class ClientGeneratedIdRequiredError extends Forbidden implements CodedErrorResponse
{
    use PointsAtDocumentMembers;

    public const string CODE = 'CLIENT_GENERATED_ID_REQUIRED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
