<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Exceptions;

/**
 * A response that does not match the schema the server documents for it.
 *
 * Thrown only when response validation is switched on. {@see self::$violations} lists each failing
 * JSON pointer within the body with what is wrong there.
 */
final class SchemaViolation extends \UnexpectedValueException implements JsonApiClientException
{
    /**
     * @param array<string, list<string>> $violations messages by JSON pointer within the body
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly int $status,
        public readonly array $violations,
    ) {
        $lines = [];
        foreach ($violations as $pointer => $messages) {
            foreach ($messages as $message) {
                $lines[] = ($pointer === '' ? '/' : $pointer) . ': ' . $message;
            }
        }

        parent::__construct(\sprintf("The %d response to %s %s does not match its documented schema:\n  %s", $status, $method, $path, \implode("\n  ", $lines)));
    }
}
