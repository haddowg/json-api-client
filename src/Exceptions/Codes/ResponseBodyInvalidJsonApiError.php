<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\ServerError;

/**
 * `RESPONSE_BODY_INVALID_JSON_API`: Response body is an invalid JSON:API document.
 *
 * The server produced a response that breaks the JSON:API document structure, which is a fault on
 * its side. Retrying won't help; report it to the API's maintainers.
 *
 * Example detail: The data must match the type: object
 *
 * @generated from `components.schemas.ResponseBodyInvalidJsonApiError` by bin/generate-error-codes.php
 */
final class ResponseBodyInvalidJsonApiError extends ServerError implements CodedErrorResponse
{
    public const string CODE = 'RESPONSE_BODY_INVALID_JSON_API';

    public const int STATUS = 500;

    /**
     * @param list<Error> $errors
     */
    public function __construct(array $errors = [], ?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct(self::STATUS, $errors, $message, $previous);
    }

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
