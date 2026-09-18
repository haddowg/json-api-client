<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use haddowg\JsonApiClient\Errors\Error;

/**
 * A `406` response.
 *
 * The server cannot satisfy the negotiated `Accept` media type. Usually an extension or profile parameter the server does not implement.
 */
class NotAcceptable extends JsonApiErrorResponse
{
    public const int STATUS = 406;

    /**
     * @param list<Error> $errors
     */
    public function __construct(array $errors = [], ?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct(self::STATUS, $errors, $message, $previous);
    }
}
