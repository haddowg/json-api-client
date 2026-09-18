<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use haddowg\JsonApiClient\Errors\Error;

/**
 * A non-2xx response carrying a JSON:API error document.
 *
 * The status-based hierarchy implements this, so `catch (NotFound)` and
 * `catch (ErrorResponse $e)` followed by `$e->isNotFound()` are both available. Code-specific
 * subclasses generated per API land under the same contract, additively.
 */
interface ErrorResponse extends JsonApiClientException
{
    public function statusCode(): int;

    /**
     * Every error object the server reported, in wire order.
     *
     * @return list<Error>
     */
    public function errors(): array;

    /**
     * Errors grouped by the input path they blame.
     *
     * A write error's `source.pointer` is remapped to the shape the caller supplied
     * (`/data/attributes/title` groups under `title`), which is what makes a `422` usable
     * for form validation. A query-side `source.parameter` is used as-is, and an error the
     * server attributed to nothing groups under {@see Error::UNATTRIBUTED}.
     *
     * @return array<string, list<Error>>
     */
    public function byPath(): array;

    /**
     * The first error carrying `$code`, or null when no error does.
     */
    public function withCode(string $code): ?Error;

    public function hasStatus(int $status): bool;

    public function is4xx(): bool;

    public function is5xx(): bool;

    public function isBadRequest(): bool;

    public function isUnauthorized(): bool;

    public function isForbidden(): bool;

    public function isNotFound(): bool;

    public function isNotAcceptable(): bool;

    public function isConflict(): bool;

    public function isUnsupportedMediaType(): bool;

    public function isUnprocessable(): bool;

    /**
     * An alias of {@see self::isUnprocessable()} — 422 is JSON:API's validation status.
     */
    public function isValidationError(): bool;

    public function isRateLimited(): bool;
}
