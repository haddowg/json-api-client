<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Http;

/**
 * Encodes a custom action's field array as `multipart/form-data` or
 * `application/x-www-form-urlencoded`, for an action that accepts a form body.
 *
 * A boolean is sent as `1` or `0`, the way `http_build_query()` sends it, so both encodings agree.
 * A list sends one field per item, named `name[]`, which is the only form PHP servers read as
 * several values; an empty list sends nothing, as a browser does for an empty multi-select.
 *
 * @phpstan-type FormFields array<string, string|int|float|bool|list<string|int|float|bool>>
 * @phpstan-type MultipartFields array<string, string|int|float|bool|FilePart|list<string|int|float|bool|FilePart>>
 */
final class FormEncoding
{
    /**
     * A fresh boundary for one multipart body. Random, so it cannot be predicted into a value.
     */
    public static function boundary(): string
    {
        return 'jsonapi-' . \bin2hex(\random_bytes(16));
    }

    /**
     * @param MultipartFields $fields
     */
    public static function multipart(array $fields, string $boundary): string
    {
        $body = '';
        foreach (self::pairs($fields) as [$name, $value]) {
            $body .= '--' . $boundary . "\r\n";
            if ($value instanceof FilePart) {
                $body .= 'Content-Disposition: form-data; name="' . self::quoted($name) . '"; filename="' . self::quoted($value->filename) . "\"\r\n"
                    . 'Content-Type: ' . self::headerValue($value->contentType) . "\r\n\r\n"
                    . $value->bytes() . "\r\n";

                continue;
            }
            $body .= 'Content-Disposition: form-data; name="' . self::quoted($name) . "\"\r\n\r\n"
                . self::scalar($value) . "\r\n";
        }

        return $body . '--' . $boundary . "--\r\n";
    }

    /**
     * @param FormFields $fields
     */
    public static function urlencoded(array $fields): string
    {
        $pairs = [];
        foreach (self::pairs($fields) as [$name, $value]) {
            $pairs[] = \urlencode($name) . '=' . \urlencode(self::scalar($value));
        }

        return \implode('&', $pairs);
    }

    /**
     * Check that a value is a multipart field array, for generated code whose body type is only
     * known to be some array once the media type is chosen at run time.
     *
     * @param array<mixed> $value
     *
     * @return MultipartFields
     *
     * @throws \InvalidArgumentException when a key is not a string or a value is not a scalar, a FilePart or a list of them
     */
    public static function multipartFields(array $value): array
    {
        $fields = [];
        foreach ($value as $name => $field) {
            if (!\is_string($name)) {
                throw new \InvalidArgumentException(\sprintf('A multipart body names every field; field %d has no name.', $name));
            }
            if (\is_array($field) && \array_is_list($field)) {
                $items = [];
                foreach ($field as $item) {
                    if (!(\is_scalar($item) || $item instanceof FilePart)) {
                        throw new \InvalidArgumentException(\sprintf('A multipart list field takes a string, number, boolean or FilePart per item; an item of "%s" is %s.', $name, \get_debug_type($item)));
                    }
                    $items[] = $item;
                }
                $fields[$name] = $items;

                continue;
            }
            if (!(\is_scalar($field) || $field instanceof FilePart)) {
                throw new \InvalidArgumentException(\sprintf('A multipart body takes a string, number, boolean, FilePart or a list of them per named field; "%s" is %s.', $name, \get_debug_type($field)));
            }
            $fields[$name] = $field;
        }

        return $fields;
    }

    /**
     * Check that a value is a urlencoded field array, as {@see multipartFields()} does.
     *
     * @param array<mixed> $value
     *
     * @return FormFields
     *
     * @throws \InvalidArgumentException when a key is not a string or a value is not a scalar or a list of them
     */
    public static function formFields(array $value): array
    {
        $fields = [];
        foreach ($value as $name => $field) {
            if (!\is_string($name)) {
                throw new \InvalidArgumentException(\sprintf('A form body names every field; field %d has no name.', $name));
            }
            if (\is_array($field) && \array_is_list($field)) {
                $items = [];
                foreach ($field as $item) {
                    if (!\is_scalar($item)) {
                        throw new \InvalidArgumentException(\sprintf('A form list field takes a string, number or boolean per item; an item of "%s" is %s.', $name, \get_debug_type($item)));
                    }
                    $items[] = $item;
                }
                $fields[$name] = $items;

                continue;
            }
            if (!\is_scalar($field)) {
                throw new \InvalidArgumentException(\sprintf('A form body takes a string, number, boolean or a list of them per named field; "%s" is %s.', $name, \get_debug_type($field)));
            }
            $fields[$name] = $field;
        }

        return $fields;
    }

    /**
     * Each field as a name and one value, a list spread into one `name[]` pair per item.
     *
     * @template T of string|int|float|bool|FilePart
     *
     * @param array<string, T|list<T>> $fields
     *
     * @return list<array{string, T}>
     */
    private static function pairs(array $fields): array
    {
        $pairs = [];
        foreach ($fields as $name => $value) {
            if (!\is_array($value)) {
                $pairs[] = [$name, $value];

                continue;
            }
            $listName = \str_ends_with($name, '[]') ? $name : $name . '[]';
            foreach ($value as $item) {
                $pairs[] = [$listName, $item];
            }
        }

        return $pairs;
    }

    private static function scalar(string|int|float|bool $value): string
    {
        return \is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    }

    /**
     * A name or filename inside a quoted header parameter, escaped the way browsers escape it.
     */
    private static function quoted(string $value): string
    {
        return \str_replace(["\r", "\n", '"'], ['%0D', '%0A', '%22'], $value);
    }

    private static function headerValue(string $value): string
    {
        return \str_replace(["\r", "\n"], '', $value);
    }
}
