<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\ServerError;

/**
 * `NO_RESOURCE_REGISTERED`: No resource registered.
 *
 * The server tried to use a resource type it has nothing registered for, which is a configuration
 * fault on its side. Retrying won't help; report it to the API's maintainers.
 *
 * Example detail: No resource is registered for type "articles".
 *
 * @generated from `components.schemas.NoResourceRegisteredError` by bin/generate-error-codes.php
 */
final class NoResourceRegisteredError extends ServerError implements CodedErrorResponse
{
    public const string CODE = 'NO_RESOURCE_REGISTERED';

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
