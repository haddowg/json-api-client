<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;

/**
 * `REQUEST_BODY_INVALID_JSON`: Request body is an invalid JSON document.
 *
 * The request body is not well-formed JSON. Fix the syntax the `detail` reports and send the
 * request again.
 *
 * Example detail: Request body is an invalid JSON document: 'Syntax error'!
 *
 * @generated from `components.schemas.RequestBodyInvalidJsonError` by bin/generate-error-codes.php
 */
final class RequestBodyInvalidJsonError extends BadRequest implements CodedErrorResponse
{
    public const string CODE = 'REQUEST_BODY_INVALID_JSON';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
