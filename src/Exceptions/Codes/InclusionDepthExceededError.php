<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;
use haddowg\JsonApiClient\Exceptions\PointsAtQueryParameters;

/**
 * `INCLUSION_DEPTH_EXCEEDED`: Inclusion depth exceeded.
 *
 * An `include` path nests more relationships than the server allows. Shorten the path and fetch the
 * deeper resources with a separate request.
 *
 * Example detail: Included paths 'author.publisher.country' exceed the maximum include depth of 2
 * permitted by the endpoint!
 *
 * @generated from `components.schemas.InclusionDepthExceededError` by bin/generate-error-codes.php
 */
final class InclusionDepthExceededError extends BadRequest implements CodedErrorResponse
{
    use PointsAtQueryParameters;

    public const string CODE = 'INCLUSION_DEPTH_EXCEEDED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
