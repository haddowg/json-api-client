<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use haddowg\JsonApiClient\Errors\Error;

/**
 * A `5xx` response. The status is carried as-is, so a `502` from a gateway and a `500` from the application are distinguishable.
 */
class ServerError extends JsonApiErrorResponse
{
    /**
     * @param list<Error> $errors
     */
    public function __construct(int $status, array $errors = [], ?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct($status, $errors, $message, $previous);
    }
}
