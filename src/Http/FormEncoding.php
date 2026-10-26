<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Http;

/**
 * Encodes a custom action's field array as `multipart/form-data` or
 * `application/x-www-form-urlencoded`, for an action that accepts a form body.
 *
 * A boolean is sent as `1` or `0`, the way `http_build_query()` sends it, so both encodings agree.
 *
 * @phpstan-type FormFields array<string, string|int|float|bool>
 * @phpstan-type MultipartFields array<string, string|int|float|bool|FilePart>
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
        foreach ($fields as $name => $value) {
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
        foreach ($fields as $name => $value) {
            $pairs[] = \urlencode($name) . '=' . \urlencode(self::scalar($value));
        }

        return \implode('&', $pairs);
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
