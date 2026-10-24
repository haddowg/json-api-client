<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Resources;

use haddowg\JsonApiClient\Support\Identifier;

/**
 * What every generated pivot read is built on: the `meta.pivot` of one linkage member.
 *
 * A pivot field is read through the same typed readers as an attribute. The difference is
 * absence. A pivot takes no sparse fieldset, so a field the response left out reads as `null`,
 * and generated code reads a field the server does not always render through a nullable reader.
 * A field the server always renders is read through a strict one, which throws
 * `MalformedDocument` when the field is missing.
 */
abstract class Pivot
{
    use ReadsMembers;

    /**
     * @var array<string, mixed>
     */
    private readonly array $fields;

    final public function __construct(private readonly Identifier $member)
    {
        $this->fields = $member->pivot();
    }

    /**
     * The linkage member this pivot belongs to.
     */
    public function _identifier(): Identifier
    {
        return $this->member;
    }

    /**
     * The `meta.pivot` block as it arrived.
     *
     * @return array<string, mixed>
     */
    public function _raw(): array
    {
        return $this->fields;
    }

    protected function _attribute(string $field): mixed
    {
        return $this->fields[$field] ?? null;
    }

    protected function _subject(): string
    {
        return 'The pivot of the "' . $this->member->type . '" "' . $this->member->id . '" linkage member';
    }
}
