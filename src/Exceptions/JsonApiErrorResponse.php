<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

use haddowg\JsonApiClient\Errors\Error;
use haddowg\JsonApiClient\Errors\ErrorDocument;

/**
 * The base of the status-based exception hierarchy.
 *
 * Catch-by-type is how PHP handles errors, so a `404` arrives as {@see NotFound} rather than
 * as a single error class you have to interrogate. The matchers are still there for the cases
 * where a range is what you mean (`is5xx()`), and for parity with the TypeScript client.
 *
 * Code-specific subclasses generated from an API's error catalogue extend the status class
 * their code maps to, so an application that catches {@see BadRequest} keeps catching a
 * generated `FilterParamUnrecognized` without changing.
 */
abstract class JsonApiErrorResponse extends \RuntimeException implements ErrorResponse
{
    /**
     * @param list<Error> $errors
     */
    public function __construct(
        private readonly int $status,
        private readonly array $errors = [],
        ?string $message = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message ?? self::describe($status, $errors), $status, $previous);
    }

    /**
     * Build the exception for a status, choosing the subclass the status maps to.
     *
     * @param list<Error> $errors
     */
    public static function for(
        int $status,
        array $errors = [],
        ?string $message = null,
        ?\Throwable $previous = null,
    ): self {
        return match (true) {
            $status === BadRequest::STATUS => new BadRequest($errors, $message, $previous),
            $status === Unauthorized::STATUS => new Unauthorized($errors, $message, $previous),
            $status === Forbidden::STATUS => new Forbidden($errors, $message, $previous),
            $status === NotFound::STATUS => new NotFound($errors, $message, $previous),
            $status === NotAcceptable::STATUS => new NotAcceptable($errors, $message, $previous),
            $status === Conflict::STATUS => new Conflict($errors, $message, $previous),
            $status === UnsupportedMediaType::STATUS => new UnsupportedMediaType($errors, $message, $previous),
            $status === ValidationFailed::STATUS => new ValidationFailed($errors, $message, $previous),
            $status === TooManyRequests::STATUS => new TooManyRequests($errors, $message, $previous),
            $status >= 500 => new ServerError($status, $errors, $message, $previous),
            default => new UnexpectedResponse($status, $errors, $message, $previous),
        };
    }

    /**
     * Build the exception from a raw response body, parsing its error document.
     */
    public static function fromBody(
        int $status,
        string $body,
        ?string $message = null,
        ?\Throwable $previous = null,
    ): self {
        return self::for($status, ErrorDocument::parse($body), $message, $previous);
    }

    public function statusCode(): int
    {
        return $this->status;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function byPath(): array
    {
        $grouped = [];

        foreach ($this->errors as $error) {
            $grouped[$error->pathKey()][] = $error;
        }

        return $grouped;
    }

    public function withCode(string $code): ?Error
    {
        foreach ($this->errors as $error) {
            if ($error->code === $code) {
                return $error;
            }
        }

        return null;
    }

    public function hasStatus(int $status): bool
    {
        return $this->status === $status;
    }

    public function is4xx(): bool
    {
        return $this->status >= 400 && $this->status < 500;
    }

    public function is5xx(): bool
    {
        return $this->status >= 500 && $this->status < 600;
    }

    public function isBadRequest(): bool
    {
        return $this->hasStatus(BadRequest::STATUS);
    }

    public function isUnauthorized(): bool
    {
        return $this->hasStatus(Unauthorized::STATUS);
    }

    public function isForbidden(): bool
    {
        return $this->hasStatus(Forbidden::STATUS);
    }

    public function isNotFound(): bool
    {
        return $this->hasStatus(NotFound::STATUS);
    }

    public function isNotAcceptable(): bool
    {
        return $this->hasStatus(NotAcceptable::STATUS);
    }

    public function isConflict(): bool
    {
        return $this->hasStatus(Conflict::STATUS);
    }

    public function isUnsupportedMediaType(): bool
    {
        return $this->hasStatus(UnsupportedMediaType::STATUS);
    }

    public function isUnprocessable(): bool
    {
        return $this->hasStatus(ValidationFailed::STATUS);
    }

    public function isValidationError(): bool
    {
        return $this->isUnprocessable();
    }

    public function isRateLimited(): bool
    {
        return $this->hasStatus(TooManyRequests::STATUS);
    }

    /**
     * The default message: the status, plus the first error's own words when it gave any.
     *
     * @param list<Error> $errors
     */
    private static function describe(int $status, array $errors): string
    {
        $message = 'JSON:API request failed with status ' . $status;

        $first = $errors[0] ?? null;
        $detail = $first === null ? null : ($first->title ?? $first->detail);

        if ($detail !== null && $detail !== '') {
            $message .= ': ' . $detail;
        }

        if (\count($errors) > 1) {
            $message .= ' (and ' . (\count($errors) - 1) . ' more)';
        }

        return $message;
    }
}
