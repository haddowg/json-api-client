<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Support;

use haddowg\JsonApiClient\Exceptions\MalformedDocument;

/**
 * A JSON:API resource identifier object — the `{type, id}` pair, with any linkage `meta`.
 *
 * This is what a linked-but-not-included relation reduces to, and what a relationship
 * mutation sends. Relation setters accept one wherever they accept a resource.
 */
final class Identifier
{
    /**
     * @param array<string, mixed> $meta linkage-level meta (pivot data lives under `pivot`)
     */
    public function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly array $meta = [],
    ) {}

    /**
     * @param array<string, mixed> $meta
     */
    public static function of(string $type, string $id, array $meta = []): self
    {
        return new self($type, $id, $meta);
    }

    /**
     * Read an identifier off the wire.
     *
     * @param array<string, mixed> $raw
     *
     * @throws MalformedDocument when `type` or `id` is absent or not a string
     */
    public static function fromArray(array $raw, string $at = 'identifier'): self
    {
        $type = $raw['type'] ?? null;
        if (!\is_string($type)) {
            throw MalformedDocument::missingMember($at, 'type');
        }

        $id = $raw['id'] ?? null;
        if (!\is_string($id)) {
            throw MalformedDocument::missingMember($at . ' (type "' . $type . '")', 'id');
        }

        $meta = $raw['meta'] ?? null;

        return new self($type, $id, \is_array($meta) ? $meta : []);
    }

    /**
     * The linkage `meta.pivot` block, when the relation carries pivot data.
     *
     * @return array<string, mixed>
     */
    public function pivot(): array
    {
        $pivot = $this->meta['pivot'] ?? null;

        return \is_array($pivot) ? $pivot : [];
    }

    public function is(self $other): bool
    {
        return $this->type === $other->type && $this->id === $other->id;
    }

    /**
     * @return array{type: string, id: string, meta?: array<string, mixed>}
     */
    public function toArray(): array
    {
        $out = ['type' => $this->type, 'id' => $this->id];

        if ($this->meta !== []) {
            $out['meta'] = $this->meta;
        }

        return $out;
    }
}
