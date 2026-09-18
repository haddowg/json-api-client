<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Errors;

use haddowg\JsonApiClient\Support\Wire;

/**
 * A single JSON:API error object.
 *
 * Every member the spec defines is carried verbatim, including the raw `source`, so nothing
 * the server said is lost. The one addition is {@see self::$path}: the caller's input path,
 * remapped from `source.pointer` by the layer that built the request document and therefore
 * knows the inverse mapping.
 */
final class Error
{
    /**
     * The grouping key for an error the server attributed to nothing in particular.
     *
     * A leading underscore cannot collide with a member name or a query parameter, by the
     * same spec clause that makes the client's reserved accessors safe.
     */
    public const string UNATTRIBUTED = '_';

    /**
     * @param array<string, mixed> $meta
     * @param string|null          $path    the caller's input path, remapped from `source.pointer`
     * @param int|null             $opIndex the failing operation's index within an atomic batch
     */
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $status = null,
        public readonly ?string $code = null,
        public readonly ?string $title = null,
        public readonly ?string $detail = null,
        public readonly ?ErrorSource $source = null,
        public readonly array $meta = [],
        public readonly ?string $path = null,
        public readonly ?int $opIndex = null,
    ) {}

    /**
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        $source = Wire::map($raw, 'source');

        return new self(
            id: Wire::string($raw, 'id'),
            status: Wire::string($raw, 'status'),
            code: Wire::string($raw, 'code'),
            title: Wire::string($raw, 'title'),
            detail: Wire::string($raw, 'detail'),
            source: $source === [] ? null : ErrorSource::fromArray($source),
            meta: Wire::map($raw, 'meta'),
        );
    }

    /**
     * The raw JSON Pointer the server reported, kept as the escape hatch when the remapped
     * {@see self::$path} is not what a caller needs.
     */
    public function pointer(): ?string
    {
        return $this->source?->pointer;
    }

    /**
     * The query parameter the server blamed, for a query-side error.
     */
    public function parameter(): ?string
    {
        return $this->source?->parameter;
    }

    /**
     * The error's HTTP status as an integer. JSON:API mandates a string on the wire.
     */
    public function statusCode(): ?int
    {
        if ($this->status === null || \preg_match('/^\d{3}$/', $this->status) !== 1) {
            return null;
        }

        return (int) $this->status;
    }

    /**
     * A copy carrying the remapped input path (and, within an atomic batch, its op index).
     */
    public function withPath(string $path, ?int $opIndex = null): self
    {
        return new self(
            id: $this->id,
            status: $this->status,
            code: $this->code,
            title: $this->title,
            detail: $this->detail,
            source: $this->source,
            meta: $this->meta,
            path: $path,
            opIndex: $opIndex ?? $this->opIndex,
        );
    }

    /**
     * The key this error groups under in {@see \haddowg\JsonApiClient\Exceptions\ErrorResponse::byPath()}.
     *
     * An already-remapped `path` wins, because the layer that produced it had the descriptor.
     * Failing that a write pointer is remapped here; a query-side `source.parameter` is left
     * exactly as the server reported it, since it already names something the caller wrote.
     */
    public function pathKey(): string
    {
        if ($this->path !== null) {
            return $this->path;
        }

        $pointer = $this->pointer();
        if ($pointer !== null) {
            return SourcePointer::toPath($pointer);
        }

        return $this->parameter() ?? self::UNATTRIBUTED;
    }
}
