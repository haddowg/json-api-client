<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient;

use haddowg\JsonApiClient\Support\Wire;

/**
 * A document's `jsonapi` member: what the server says it implements.
 *
 * A typed value object rather than an array, because `ext` and `profile` are how a client
 * verifies that negotiation actually took. The client adds `profile="…"` to `Accept` when a
 * request uses a profile-gated parameter; this is where the answer comes back.
 */
final class JsonApi
{
    /**
     * @param string|null          $version the JSON:API version, or null when the server did not
     *                                      advertise one (the spec's own default is then 1.0)
     * @param list<string>         $ext     extension URIs the server implements
     * @param list<string>         $profile profile URIs the server applied to this document
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly ?string $version = null,
        public readonly array $ext = [],
        public readonly array $profile = [],
        private readonly array $meta = [],
    ) {}

    /**
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            version: Wire::string($raw, 'version'),
            ext: Wire::strings($raw, 'ext'),
            profile: Wire::strings($raw, 'profile'),
            meta: Wire::map($raw, 'meta'),
        );
    }

    /**
     * What a document that carried no `jsonapi` member reads as.
     */
    public static function absent(): self
    {
        return new self();
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    public function hasExt(string $uri): bool
    {
        return \in_array($uri, $this->ext, true);
    }

    public function hasProfile(string $uri): bool
    {
        return \in_array($uri, $this->profile, true);
    }

    /**
     * Whether the response applied the Atomic Operations extension.
     */
    public function hasAtomicOperations(): bool
    {
        return $this->hasExt(MediaType::ATOMIC_OPERATIONS);
    }
}
