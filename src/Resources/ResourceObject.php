<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Resources;

use haddowg\JsonApiClient\Document;
use haddowg\JsonApiClient\Exceptions\FieldNotSelectedException;
use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Exceptions\RelationNotIncludedException;
use haddowg\JsonApiClient\Support\Identifier;
use haddowg\JsonApiClient\Support\Wire;

/**
 * What every generated resource DTO is built on: the wire read, the two absence throws, and the
 * reserved accessors.
 *
 * Generated code declares the shape — `title(): string`, `artist(): ?Artist` — and calls in here
 * for the value, so coercion and the absence rules live in one place rather than in thirteen
 * copies of the same emitted body. Everything reserved carries a leading underscore, which the
 * JSON:API member-name grammar guarantees no API can ever collide with, and that applies to the
 * protected readers too: a resource with an attribute named `string` still compiles.
 */
abstract class ResourceObject
{
    /**
     * @var array<string, mixed>
     */
    private readonly array $attributes;

    /**
     * @param array<string, mixed> $raw the resource object as it arrived
     */
    protected function __construct(
        private readonly Identifier $identifier,
        private readonly array $raw,
        private readonly ResourceContext $context,
    ) {
        $this->attributes = Wire::map($raw, 'attributes');
    }

    /**
     * The `{type, id}` pair, which is also what a relationship write sends.
     */
    public function _identifier(): Identifier
    {
        return $this->identifier;
    }

    /**
     * The resource object exactly as it arrived, for anything the generated surface does not
     * model.
     *
     * @return array<string, mixed>
     */
    public function _raw(): array
    {
        return $this->raw;
    }

    /**
     * @return array<string, mixed>
     */
    public function _meta(): array
    {
        return Wire::map($this->raw, 'meta');
    }

    /**
     * @return array<string, mixed>
     */
    public function _links(): array
    {
        return Wire::map($this->raw, 'links');
    }

    public function _link(string $relation): ?string
    {
        return Wire::href($this->_links()[$relation] ?? null);
    }

    /**
     * This resource's own address.
     */
    public function _self(): ?string
    {
        return $this->_link('self');
    }

    /**
     * The document this resource came from, shared by reference with every other resource in the
     * same response — so `$album->_document()` and `$album->artist->_document()` are one object.
     */
    public function _document(): Document
    {
        return $this->context->document();
    }

    /**
     * @return array<string, mixed>
     */
    protected function _attributes(): array
    {
        return $this->attributes;
    }

    protected function _context(): ResourceContext
    {
        return $this->context;
    }

    /**
     * The context a resource hydrated from this one's `$relation` belongs in, one include path
     * deeper.
     */
    protected function _child(string $relation): ResourceContext
    {
        return $this->context->at($relation);
    }

    /**
     * An attribute's raw value.
     *
     * @throws FieldNotSelectedException when the response did not carry the attribute, whether a
     *                                   sparse fieldset excluded it or the server simply omitted it
     */
    protected function _attribute(string $field): mixed
    {
        if (!\array_key_exists($field, $this->attributes)) {
            throw FieldNotSelectedException::for(
                $this->identifier->type,
                $field,
                $this->context->fieldset($this->identifier->type),
            );
        }

        return $this->attributes[$field];
    }

    protected function _string(string $field): string
    {
        $value = $this->_attribute($field);

        if (!\is_string($value)) {
            throw $this->_wrongType($field, 'a string');
        }

        return $value;
    }

    protected function _nullableString(string $field): ?string
    {
        $value = $this->_attribute($field);

        if ($value !== null && !\is_string($value)) {
            throw $this->_wrongType($field, 'a string or null');
        }

        return $value;
    }

    protected function _bool(string $field): bool
    {
        $value = $this->_attribute($field);

        if (!\is_bool($value)) {
            throw $this->_wrongType($field, 'a boolean');
        }

        return $value;
    }

    protected function _int(string $field): int
    {
        $value = $this->_attribute($field);

        if (!\is_int($value)) {
            throw $this->_wrongType($field, 'an integer');
        }

        return $value;
    }

    /**
     * A JSON `number`, which arrives as an int whenever it has no fractional part.
     */
    protected function _float(string $field): float
    {
        $value = $this->_attribute($field);

        if (!\is_int($value) && !\is_float($value)) {
            throw $this->_wrongType($field, 'a number');
        }

        return (float) $value;
    }

    protected function _nullableFloat(string $field): ?float
    {
        $value = $this->_attribute($field);

        if ($value === null) {
            return null;
        }

        if (!\is_int($value) && !\is_float($value)) {
            throw $this->_wrongType($field, 'a number or null');
        }

        return (float) $value;
    }

    /**
     * Coerce a `date`, `date-time` or `time` string to the type PHP actually works in, which is
     * the same coercion the server performs in the other direction.
     */
    protected function _dateTime(string $field): \DateTimeImmutable
    {
        $value = $this->_nullableDateTime($field);

        if ($value === null) {
            throw $this->_wrongType($field, 'a date/time string');
        }

        return $value;
    }

    protected function _nullableDateTime(string $field): ?\DateTimeImmutable
    {
        $value = $this->_attribute($field);

        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            throw $this->_wrongType($field, 'a date/time string or null');
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception $e) {
            throw new MalformedDocument(
                \sprintf(
                    'The "%s" attribute of "%s" is not a date/time this client can parse: %s. '
                    . 'The OpenAPI document states only `format: date-time`, so a server configured '
                    . 'with a non-ISO format cannot be read.',
                    $field,
                    $this->identifier->type,
                    $value,
                ),
                0,
                $e,
            );
        }
    }

    /**
     * @template TEnum of \BackedEnum
     *
     * @param class-string<TEnum> $enum
     *
     * @return TEnum
     */
    protected function _enum(string $field, string $enum): \BackedEnum
    {
        $value = $this->_nullableEnum($field, $enum);

        if ($value === null) {
            throw $this->_wrongType($field, 'one of ' . $enum);
        }

        return $value;
    }

    /**
     * @template TEnum of \BackedEnum
     *
     * @param class-string<TEnum> $enum
     *
     * @return TEnum|null
     */
    protected function _nullableEnum(string $field, string $enum): ?\BackedEnum
    {
        $value = $this->_attribute($field);

        if ($value === null) {
            return null;
        }

        if (!\is_string($value) && !\is_int($value)) {
            throw $this->_wrongType($field, 'one of ' . $enum);
        }

        $case = $enum::tryFrom($value);

        if ($case === null) {
            throw $this->_wrongType($field, 'one of ' . $enum);
        }

        return $case;
    }

    /**
     * @return list<string>
     */
    protected function _stringList(string $field): array
    {
        $value = $this->_attribute($field);

        if (!\is_array($value) || !\array_is_list($value)) {
            throw $this->_wrongType($field, 'an array of strings');
        }

        $out = [];

        foreach ($value as $member) {
            if (!\is_string($member)) {
                throw $this->_wrongType($field, 'an array of strings');
            }

            $out[] = $member;
        }

        return $out;
    }

    /**
     * A nested object attribute, unnarrowed. Generated code guards it into its declared shape.
     *
     * @return array<string, mixed>|null
     */
    protected function _nullableMap(string $field): ?array
    {
        $value = $this->_attribute($field);

        if ($value === null) {
            return null;
        }

        if (!\is_array($value) || \array_is_list($value)) {
            throw $this->_wrongType($field, 'an object or null');
        }

        /** @var array<string, mixed> $out */
        $out = [];

        foreach ($value as $key => $member) {
            $out[(string) $key] = $member;
        }

        return $out;
    }

    /**
     * The relationship object as the response stated it. Never throws: this is the door that
     * stays open when a relation carries no value.
     */
    protected function _relationship(string $name, bool $toOne): Relationship
    {
        $relationships = Wire::map($this->raw, 'relationships');
        $raw = $relationships[$name] ?? null;

        if (!\is_array($raw) || \array_is_list($raw)) {
            return Relationship::absent($name, $toOne);
        }

        /** @var array<string, mixed> $raw */
        return Relationship::read($name, $raw, $toOne, $this->context->isIncluded($name));
    }

    /**
     * The resource object a hydrated to-one relation points at, or null when it points at
     * nothing.
     *
     * @param string $companion the always-safe accessor to name in the message
     *
     * @return array<string, mixed>|null
     *
     * @throws RelationNotIncludedException when the read did not include this relation
     */
    protected function _includedOne(string $name, string $companion): ?array
    {
        $relationship = $this->_relationship($name, true);

        if (!$relationship->isIncluded()) {
            throw $this->_notIncluded($name, $companion);
        }

        $identifier = $relationship->identifier();

        if ($identifier === null) {
            return null;
        }

        return $this->context->resolve($identifier) ?? throw $this->_notIncluded($name, $companion);
    }

    /**
     * The resource objects a hydrated to-many relation points at.
     *
     * @param string $companion the always-safe accessor to name in the message
     *
     * @return list<array<string, mixed>>
     *
     * @throws RelationNotIncludedException when the read did not include this relation
     */
    protected function _includedMany(string $name, string $companion): array
    {
        $relationship = $this->_relationship($name, false);

        if (!$relationship->isIncluded()) {
            throw $this->_notIncluded($name, $companion);
        }

        $out = [];

        foreach ($relationship->identifiers() as $identifier) {
            $out[] = $this->context->resolve($identifier) ?? throw $this->_notIncluded($name, $companion);
        }

        return $out;
    }

    private function _notIncluded(string $name, string $companion): RelationNotIncludedException
    {
        return RelationNotIncludedException::for(
            $this->identifier->type,
            $name,
            $this->context->pathTo($name),
            $companion,
        );
    }

    private function _wrongType(string $field, string $expected): MalformedDocument
    {
        return MalformedDocument::unexpectedMember(
            'The "' . $this->identifier->type . '" resource',
            $field,
            $expected,
        );
    }
}
