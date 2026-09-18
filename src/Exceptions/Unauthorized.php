<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use haddowg\JsonApiClient\Errors\Error;

/**
 * A `401` response.
 *
 * The request carried no usable credentials. A per-request header provider is the place to refresh them.
 */
class Unauthorized extends JsonApiErrorResponse
{
    public const int STATUS = 401;

    /**
     * @param list<Error> $errors
     */
    public function __construct(array $errors = [], ?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct(self::STATUS, $errors, $message, $previous);
    }
}
