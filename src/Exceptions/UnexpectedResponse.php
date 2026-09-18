<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use haddowg\JsonApiClient\Errors\Error;

/**
 * A non-2xx response whose status maps to no more specific class. The fallback that keeps an unfamiliar status catchable rather than unhandled.
 */
class UnexpectedResponse extends JsonApiErrorResponse
{
    /**
     * @param list<Error> $errors
     */
    public function __construct(int $status, array $errors = [], ?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct($status, $errors, $message, $previous);
    }
}
