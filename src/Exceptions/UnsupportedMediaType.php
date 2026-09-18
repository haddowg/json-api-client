<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use haddowg\JsonApiClient\Errors\Error;

/**
 * A `415` response.
 *
 * The server will not accept the request`s `Content-Type`.
 */
class UnsupportedMediaType extends JsonApiErrorResponse
{
    public const int STATUS = 415;

    /**
     * @param list<Error> $errors
     */
    public function __construct(array $errors = [], ?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct(self::STATUS, $errors, $message, $previous);
    }
}
