<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\ServerError;

/**
 * `APPLICATION_ERROR`: Application error.
 *
 * The server failed to handle the request because of a fault on its side, not because of anything
 * in the request. Retrying won't help; report it to the API's maintainers.
 *
 * Example detail: An application error has occurred!
 *
 * @generated from `components.schemas.ApplicationError` by bin/generate-error-codes.php
 */
final class ApplicationError extends ServerError implements CodedErrorResponse
{
    public const string CODE = 'APPLICATION_ERROR';

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
