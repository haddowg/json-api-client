<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\NotAcceptable;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;

/**
 * `MEDIA_TYPE_UNACCEPTABLE`: The provided media type is unacceptable.
 *
 * @generated from `components.schemas.MediaTypeUnacceptableError` by bin/generate-error-codes.php
 */
final class MediaTypeUnacceptableError extends NotAcceptable implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'MEDIA_TYPE_UNACCEPTABLE';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
