<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use haddowg\JsonApiClient\Errors\Error;

/**
 * A `400` response.
 *
 * The server rejected the request as malformed. Under the strict query validation our servers apply, an unrecognised filter, sort or include token lands here.
 */
class BadRequest extends JsonApiErrorResponse
{
    public const int STATUS = 400;

    /**
     * @param list<Error> $errors
     */
    public function __construct(array $errors = [], ?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct(self::STATUS, $errors, $message, $previous);
    }
}
