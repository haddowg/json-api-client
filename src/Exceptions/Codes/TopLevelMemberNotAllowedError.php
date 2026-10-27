<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;

/**
 * `TOP_LEVEL_MEMBER_NOT_ALLOWED`: Top-level member is not allowed.
 *
 * The request body has a top-level `included` member but no `data`. Remove `included`, or send it
 * alongside `data`.
 *
 * Example detail: If a document does not contain a top-level "data" key, the "included" member must
 * not be present either.
 *
 * @generated from `components.schemas.TopLevelMemberNotAllowedError` by bin/generate-error-codes.php
 */
final class TopLevelMemberNotAllowedError extends BadRequest implements CodedErrorResponse
{
    public const string CODE = 'TOP_LEVEL_MEMBER_NOT_ALLOWED';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
