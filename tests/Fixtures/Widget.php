<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Fixtures;

use haddowg\JsonApiClient\Resources\ResourceContext;
use haddowg\JsonApiClient\Resources\ResourceObject;
use haddowg\JsonApiClient\Support\Identifier;

/**
 * Opens the protected attribute readers a generated resource class calls, so they can be
 * exercised against hand-built resource objects.
 */
final class Widget extends ResourceObject
{
    /**
     * @param array<string, mixed> $attributes
     */
    public static function with(array $attributes): self
    {
        $raw = ['type' => 'widgets', 'id' => '1', 'attributes' => $attributes];

        return new self(Identifier::of('widgets', '1'), $raw, ResourceContext::of(['data' => $raw]));
    }

    public function nullableInt(string $field): ?int
    {
        return $this->_nullableInt($field);
    }

    public function nullableBool(string $field): ?bool
    {
        return $this->_nullableBool($field);
    }

    /**
     * @return list<mixed>
     */
    public function list(string $field): array
    {
        return $this->_list($field);
    }

    /**
     * @return list<mixed>|null
     */
    public function nullableList(string $field): ?array
    {
        return $this->_nullableList($field);
    }

    /**
     * @return array<string, mixed>
     */
    public function map(string $field): array
    {
        return $this->_map($field);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function nullableMap(string $field): ?array
    {
        return $this->_nullableMap($field);
    }
}
