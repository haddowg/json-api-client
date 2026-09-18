<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Resources;

use haddowg\JsonApiClient\Support\Identifier;
use haddowg\JsonApiClient\Support\Wire;

/**
 * A relationship object as the response stated it: linkage, links and meta, with no resource
 * hydrated.
 *
 * This is what `_rel()` hands back, and it is the reason `withCount` and the un-included throw
 * can coexist. `withCount=tracks` returns `meta.total` for a relation that was deliberately not
 * included, so the count is unreachable through `$album->tracks` — which throws, correctly.
 * Reading it here needs no new concept: `_rel()` is already the uniform door to a relation
 * carrying no value.
 */
final class Relationship
{
    /**
     * @param list<Identifier>     $identifiers linkage, empty when the response carried none
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $links
     * @param bool                 $hasLinkage  whether the response stated linkage at all, which
     *                                          an empty to-many and an absent `data` do not share
     */
    private function __construct(
        public readonly string $name,
        public readonly bool $toOne,
        private readonly array $identifiers,
        private readonly array $meta,
        private readonly array $links,
        private readonly bool $hasLinkage,
        private readonly bool $included,
    ) {}

    /**
     * Read a relationship object off a resource object's `relationships` member.
     *
     * @param array<string, mixed> $raw
     */
    public static function read(string $name, array $raw, bool $toOne, bool $included = false): self
    {
        $hasLinkage = \array_key_exists('data', $raw);
        $data = $raw['data'] ?? null;
        $identifiers = [];

        if (\is_array($data) && \array_is_list($data)) {
            foreach ($data as $member) {
                if (\is_array($member)) {
                    /** @var array<string, mixed> $member */
                    $identifiers[] = Identifier::fromArray($member, 'the "' . $name . '" relationship');
                }
            }
        } elseif (\is_array($data)) {
            /** @var array<string, mixed> $data */
            $identifiers[] = Identifier::fromArray($data, 'the "' . $name . '" relationship');
        }

        return new self(
            $name,
            $toOne,
            $identifiers,
            Wire::map($raw, 'meta'),
            Wire::map($raw, 'links'),
            $hasLinkage,
            $included,
        );
    }

    /**
     * What a resource object that declared no such relationship reads as.
     */
    public static function absent(string $name, bool $toOne): self
    {
        return new self($name, $toOne, [], [], [], false, false);
    }

    /**
     * The Countable profile's answer for this relation, or null when the read did not ask for
     * one. A relation is counted through `withCount`, never as a side effect of including it.
     */
    public function total(): ?int
    {
        return Wire::int($this->meta, 'total');
    }

    /**
     * @return list<Identifier>
     */
    public function identifiers(): array
    {
        return $this->identifiers;
    }

    /**
     * The single identifier of a to-one relation, or null when it points at nothing.
     */
    public function identifier(): ?Identifier
    {
        return $this->identifiers[0] ?? null;
    }

    /**
     * Whether linkage points at anything. False for an empty to-many and for a null to-one,
     * and false when the response stated no linkage at all.
     */
    public function isEmpty(): bool
    {
        return $this->identifiers === [];
    }

    public function hasLinkage(): bool
    {
        return $this->hasLinkage;
    }

    /**
     * Whether the read named this relation in its include paths.
     */
    public function isIncluded(): bool
    {
        return $this->included;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * @return array<string, mixed>
     */
    public function links(): array
    {
        return $this->links;
    }

    public function link(string $relation): ?string
    {
        return Wire::href($this->links[$relation] ?? null);
    }

    /**
     * The relationship endpoint's own address, which reads and writes linkage.
     */
    public function self(): ?string
    {
        return $this->link('self');
    }

    /**
     * The related-resources endpoint, which reads the resources themselves.
     */
    public function related(): ?string
    {
        return $this->link('related');
    }
}
