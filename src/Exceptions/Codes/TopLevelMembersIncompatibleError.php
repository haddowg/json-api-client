<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions\Codes;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Exceptions\BadRequest;
use haddowg\JsonApiClient\Exceptions\CodedErrorResponse;

/**
 * `TOP_LEVEL_MEMBERS_INCOMPATIBLE`: Top-level members are incompatible.
 *
 * The request body has both `data` and `errors` at the top level, which JSON:API forbids. Send the
 * resource under `data` alone.
 *
 * Example detail: The members "data" and "errors" cannot coexist in the same document
 *
 * @generated from `components.schemas.TopLevelMembersIncompatibleError` by bin/generate-error-codes.php
 */
final class TopLevelMembersIncompatibleError extends BadRequest implements CodedErrorResponse
{
    public const string CODE = 'TOP_LEVEL_MEMBERS_INCOMPATIBLE';

    /**
     * @param list<Error> $errors
     */
    public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
    {
        return new self($errors, $message, $previous);
    }
}
