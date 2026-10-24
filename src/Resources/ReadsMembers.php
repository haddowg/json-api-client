<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Resources;

use haddowg\JsonApiClient\Exceptions\MalformedDocument;

/**
 * The typed readers generated code reads a member's value through, shared by a resource's
 * attributes and a linkage member's pivot.
 *
 * Each one throws `MalformedDocument` when the value is not the type the document declared, naming
 * the member and where it was read from, so a server that sends the wrong thing fails at the read
 * rather than somewhere downstream.
 */
trait ReadsMembers
{
    /**
     * A member's raw value.
     */
    abstract protected function _attribute(string $field): mixed;

    /**
     * Where the members are read from, for a failure message: `The "albums" resource`.
     */
    abstract protected function _subject(): string;

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

    protected function _nullableBool(string $field): ?bool
    {
        $value = $this->_attribute($field);

        if ($value !== null && !\is_bool($value)) {
            throw $this->_wrongType($field, 'a boolean or null');
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

    protected function _nullableInt(string $field): ?int
    {
        $value = $this->_attribute($field);

        if ($value !== null && !\is_int($value)) {
            throw $this->_wrongType($field, 'an integer or null');
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
                    '%s has a "%s" member that is not a date/time this client can parse: %s. '
                    . 'The OpenAPI document states only `format: date-time`, so a server configured '
                    . 'with a non-ISO format cannot be read.',
                    $this->_subject(),
                    $field,
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
     * An array attribute, unnarrowed. Generated code guards each member into its declared type;
     * `_stringList()` is the common case already narrowed.
     *
     * @return list<mixed>
     */
    protected function _list(string $field): array
    {
        return $this->_nullableList($field) ?? throw $this->_wrongType($field, 'an array');
    }

    /**
     * @return list<mixed>|null
     */
    protected function _nullableList(string $field): ?array
    {
        $value = $this->_attribute($field);

        if ($value === null) {
            return null;
        }

        if (!\is_array($value) || !\array_is_list($value)) {
            throw $this->_wrongType($field, 'an array or null');
        }

        return $value;
    }

    /**
     * A nested object attribute, unnarrowed. Generated code guards it into its declared shape.
     *
     * @return array<string, mixed>
     */
    protected function _map(string $field): array
    {
        return $this->_nullableMap($field) ?? throw $this->_wrongType($field, 'an object');
    }

    /**
     * A nullable nested object attribute, unnarrowed.
     *
     * An empty object decodes to `[]`, which is also an empty list, so it is read as an object
     * with no members rather than rejected.
     *
     * @return array<string, mixed>|null
     */
    protected function _nullableMap(string $field): ?array
    {
        $value = $this->_attribute($field);

        if ($value === null) {
            return null;
        }

        if (!\is_array($value) || ($value !== [] && \array_is_list($value))) {
            throw $this->_wrongType($field, 'an object or null');
        }

        /** @var array<string, mixed> $out */
        $out = [];

        foreach ($value as $key => $member) {
            $out[(string) $key] = $member;
        }

        return $out;
    }

    private function _wrongType(string $field, string $expected): MalformedDocument
    {
        return MalformedDocument::unexpectedMember($this->_subject(), $field, $expected);
    }
}
