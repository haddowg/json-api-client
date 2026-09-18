<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Errors;

/**
 * An error object's `source` member: what in the request caused the error.
 *
 * JSON:API defines three, and they are mutually exclusive in practice — `pointer` for a
 * member of the request document, `parameter` for a query parameter, `header` for a
 * request header.
 */
final class ErrorSource
{
    public function __construct(
        public readonly ?string $pointer = null,
        public readonly ?string $parameter = null,
        public readonly ?string $header = null,
    ) {}

    /**
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            \is_string($raw['pointer'] ?? null) ? $raw['pointer'] : null,
            \is_string($raw['parameter'] ?? null) ? $raw['parameter'] : null,
            \is_string($raw['header'] ?? null) ? $raw['header'] : null,
        );
    }

    public function isEmpty(): bool
    {
        return $this->pointer === null && $this->parameter === null && $this->header === null;
    }
}
