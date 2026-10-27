<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;

/**
 * `REQUEST_BODY_INVALID_JSON_API`: Request body is an invalid JSON:API document.
 *
 * The request body is JSON but breaks the JSON:API document structure or the schema the server
 * validates this resource against. Each violation is its own error, with `source.pointer` set where
 * the server could locate it.
 *
 * Example detail: The data must match the type: object
 *
 * @generated from `components.schemas.RequestBodyInvalidJsonApiError` by bin/generate-error-codes.php
 */
final class RequestBodyInvalidJsonApiError extends BadRequest implements CodedErrorResponse
{
    public const string CODE = 'REQUEST_BODY_INVALID_JSON_API';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
