<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use haddowg\JsonApiClient\Errors\Error;

/**
 * An error response specialised to one catalogued error code.
 *
 * Every code class extends the status class its code maps to, so catching {@see BadRequest}
 * still catches `FilterValueInvalidError`. This contract is how the status-to-class dispatch
 * builds one without knowing which status class it extends.
 */
interface CodedErrorResponse extends ErrorResponse
{
    /**
     * @param list<Error> $errors every one carrying this class's code
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static;
}
