<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use haddowg\JsonApiClient\Errors\Error;

/**
 * A `409` response.
 *
 * The request conflicts with the current state of the resource — a duplicate client-generated id, or a type that does not match the endpoint.
 */
class Conflict extends JsonApiErrorResponse
{
    public const int STATUS = 409;

    /**
     * @param list<Error> $errors
     */
    public function __construct(array $errors = [], ?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct(self::STATUS, $errors, $message, $previous);
    }
}
