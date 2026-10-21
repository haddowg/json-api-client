<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;
use haddowg\JsonApiClient\Exceptions\UnsupportedMediaType;

/**
 * `MEDIA_TYPE_UNSUPPORTED`: The provided media type is unsupported.
 *
 * @generated from `components.schemas.MediaTypeUnsupportedError` by bin/generate-error-codes.php
 */
final class MediaTypeUnsupportedError extends UnsupportedMediaType implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'MEDIA_TYPE_UNSUPPORTED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
