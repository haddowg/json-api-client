<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use haddowg\JsonApiClient\Errors\Error;

/**
 * A `422` response.
 *
 * The document was well-formed and its contents failed validation. This is the status {@see \haddowg\JsonApiClient\Exceptions\ErrorResponse::byPath()} exists for.
 */
class ValidationFailed extends JsonApiErrorResponse
{
    public const int STATUS = 422;

    /**
     * @param list<Error> $errors
     */
    public function __construct(array $errors = [], ?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct(self::STATUS, $errors, $message, $previous);
    }
}
