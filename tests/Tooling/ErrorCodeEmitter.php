<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Tooling;

/**
 * Emits the runtime's per-code exception classes from an OpenAPI document's error catalogue.
 *
 * Reads only `components.schemas`: every `<Name>Error` variant narrows `code` and `status` to a
 * constant and may require one `source` member. Output is keyed by path relative to the
 * package root, so `bin/generate-error-codes.php` writes it and `ErrorCodeParityTest` compares
 * it against what is committed.
 */
final class ErrorCodeEmitter
{
    private const array PARENTS = [
        400 => 'BadRequest',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'NotFound',
        406 => 'NotAcceptable',
        409 => 'Conflict',
        415 => 'UnsupportedMediaType',
        422 => 'ValidationFailed',
        429 => 'TooManyRequests',
        500 => 'ServerError',
    ];

    private const array SOURCE_TRAITS = [
        'pointer' => 'PointsAtDocumentMembers',
        'parameter' => 'PointsAtQueryParameters',
    ];

    public const string DIRECTORY = 'src/Exceptions/Codes';

    /**
     * @param array<array-key, mixed> $document a decoded OpenAPI document
     *
     * @return array<string, string> file contents keyed by path relative to the package root
     */
    public static function emit(array $document): array
    {
        $files = [];
        $map = [];

        foreach (self::variants($document) as $variant) {
            $files[self::DIRECTORY . '/' . $variant['class'] . '.php'] = self::codeClass($variant);
            $map[$variant['code']] = $variant['class'];
        }

        \ksort($map, \SORT_STRING);
        $files[self::DIRECTORY . '/ErrorCodes.php'] = self::codeMap($map);
        \ksort($files, \SORT_STRING);

        return $files;
    }

    /**
     * The catalogue alone, as a document `emit()` accepts: the committed copy the parity test
     * regenerates from.
     *
     * @param array<array-key, mixed> $document
     *
     * @return array{components: array{schemas: array<string, mixed>}}
     */
    public static function catalogue(array $document): array
    {
        $schemas = [];
        foreach (self::schemas($document) as $name => $schema) {
            if (self::isVariant($name)) {
                $schemas[$name] = $schema;
            }
        }
        \ksort($schemas, \SORT_STRING);

        return ['components' => ['schemas' => $schemas]];
    }

    /**
     * @param array<array-key, mixed> $document
     *
     * @return list<array{class: string, code: string, status: int, title: string, source: string|null}>
     */
    private static function variants(array $document): array
    {
        $variants = [];

        foreach (self::schemas($document) as $name => $schema) {
            if (!self::isVariant($name) || !\is_array($schema)) {
                continue;
            }

            $narrowing = null;
            foreach (\is_array($schema['allOf'] ?? null) ? $schema['allOf'] : [] as $part) {
                if (\is_array($part) && \is_array($part['properties'] ?? null)) {
                    $narrowing = $part['properties'];
                }
            }

            $code = \is_array($narrowing) && \is_array($narrowing['code'] ?? null) ? ($narrowing['code']['const'] ?? null) : null;
            $status = \is_array($narrowing) && \is_array($narrowing['status'] ?? null) ? ($narrowing['status']['const'] ?? null) : null;
            if (!\is_string($code) || !\is_string($status) || !isset(self::PARENTS[(int) $status])) {
                throw new \UnexpectedValueException(\sprintf('components.schemas.%s narrows no code and known status.', $name));
            }

            $required = \is_array($narrowing['source'] ?? null) && \is_array($narrowing['source']['required'] ?? null)
                ? $narrowing['source']['required']
                : [];
            $source = null;
            foreach (\array_keys(self::SOURCE_TRAITS) as $member) {
                if (\in_array($member, $required, true)) {
                    $source = $member;
                }
            }

            $variants[] = [
                'class' => $name,
                'code' => $code,
                'status' => (int) $status,
                'title' => \is_string($schema['title'] ?? null) ? $schema['title'] : $code,
                'source' => $source,
            ];
        }

        \usort($variants, static fn(array $a, array $b): int => \strcmp($a['class'], $b['class']));

        return $variants;
    }

    /**
     * @param array{class: string, code: string, status: int, title: string, source: string|null} $variant
     */
    private static function codeClass(array $variant): string
    {
        $parent = self::PARENTS[$variant['status']];
        $trait = $variant['source'] !== null ? self::SOURCE_TRAITS[$variant['source']] : null;

        $imports = ['haddowg\\JsonApiClient\\Errors\\Error', 'haddowg\\JsonApiClient\\Exceptions\\' . $parent, 'haddowg\\JsonApiClient\\Exceptions\\CodedErrorResponse'];
        if ($trait !== null) {
            $imports[] = 'haddowg\\JsonApiClient\\Exceptions\\' . $trait;
        }
        \usort($imports, static fn(string $a, string $b): int => \strcasecmp($a, $b));

        $body = [];
        if ($trait !== null) {
            $body[] = '    use ' . $trait . ";\n";
        }
        $body[] = "    public const string CODE = '" . $variant['code'] . "';\n";

        if ($parent === 'ServerError') {
            $body[] = '    public const int STATUS = ' . $variant['status'] . ";\n";
            $body[] = <<<'PHP'
                    /**
                     * @param list<Error> $errors
                     */
                    public function __construct(array $errors = [], ?string $message = null, ?\Throwable $previous = null)
                    {
                        parent::__construct(self::STATUS, $errors, $message, $previous);
                    }

                PHP;
        }

        $body[] = <<<'PHP'
                /**
                 * @param list<Error> $errors
                 */
                public static function fromErrors(array $errors, ?string $message = null, ?\Throwable $previous = null): static
                {
                    return new self($errors, $message, $previous);
                }

            PHP;

        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace haddowg\\JsonApiClient\\Exceptions\\Codes;\n\n"
            . \implode('', \array_map(static fn(string $import): string => 'use ' . $import . ";\n", $imports))
            . "\n/**\n * `" . $variant['code'] . '`: ' . $variant['title'] . ".\n *\n"
            . ' * @generated from `components.schemas.' . $variant['class'] . "` by bin/generate-error-codes.php\n */\n"
            . 'final class ' . $variant['class'] . ' extends ' . $parent . " implements CodedErrorResponse\n{\n"
            . \implode("\n", $body)
            . "}\n";
    }

    /**
     * @param array<string, string> $map code => class
     */
    private static function codeMap(array $map): string
    {
        $entries = '';
        foreach ($map as $code => $class) {
            $entries .= "        '" . $code . "' => " . $class . "::class,\n";
        }

        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace haddowg\\JsonApiClient\\Exceptions\\Codes;\n\n"
            . "use haddowg\\JsonApiClient\\Exceptions\\CodedErrorResponse;\n"
            . "use haddowg\\JsonApiClient\\Exceptions\\JsonApiErrorResponse;\n\n"
            . "/**\n * Every catalogued error code the runtime ships a class for, keyed by code.\n *\n"
            . " * @generated by bin/generate-error-codes.php\n */\n"
            . "final class ErrorCodes\n{\n"
            . "    /**\n     * @var array<string, class-string<CodedErrorResponse&JsonApiErrorResponse>>\n     */\n"
            . "    public const array MAP = [\n" . $entries . "    ];\n}\n";
    }

    private static function isVariant(string $name): bool
    {
        return $name !== 'Error' && \str_ends_with($name, 'Error');
    }

    /**
     * @param array<array-key, mixed> $document
     *
     * @return array<string, mixed>
     */
    private static function schemas(array $document): array
    {
        $components = \is_array($document['components'] ?? null) ? $document['components'] : [];
        $schemas = \is_array($components['schemas'] ?? null) ? $components['schemas'] : [];

        $out = [];
        foreach ($schemas as $name => $schema) {
            $out[(string) $name] = $schema;
        }

        return $out;
    }
}
