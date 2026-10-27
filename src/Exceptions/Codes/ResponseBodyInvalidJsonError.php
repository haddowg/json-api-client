<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\ServerError;

/**
 * `RESPONSE_BODY_INVALID_JSON`: Response body is an invalid JSON document.
 *
 * The server produced a response body that is not valid JSON, which is a fault on its side.
 * Retrying won't help; report it to the API's maintainers.
 *
 * Example detail: Response body is an invalid JSON document: 'Syntax error'!
 *
 * @generated from `components.schemas.ResponseBodyInvalidJsonError` by bin/generate-error-codes.php
 */
final class ResponseBodyInvalidJsonError extends ServerError implements CodedErrorResponse
{
    public const string CODE = 'RESPONSE_BODY_INVALID_JSON';

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
