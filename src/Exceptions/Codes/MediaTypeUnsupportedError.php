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
 * The `Content-Type` is `application/vnd.api+json` with a media type parameter other than `ext` or
 * `profile`, or with an `ext` the server does not support. Send `Content-Type:
 * application/vnd.api+json` with only the parameters the server supports.
 *
 * Example detail: The media type 'application/vnd.api+json; charset=utf-8' is unsupported in the
 * 'Content-Type' header!
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
