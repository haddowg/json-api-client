<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Validation;

use haddowg\JsonApiClient\Exceptions\SchemaViolation;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Psr\Http\Message\RequestInterface;

/**
 * Validates each response against the schema the server's OpenAPI document gives its operation,
 * with `opis/json-schema`.
 *
 * ```php
 * new ClientOptions(
 *     baseUrl: 'https://music.example',
 *     validator: OpenApiValidator::fromFile(__DIR__ . '/openapi.json'),
 * );
 * ```
 *
 * The operation is found by method and path template, the response by status (falling back to
 * `2XX` and `default`), and its schema by media type. A response the document says nothing about is
 * not validated: the point is to catch a server that breaks its own contract, not one that has
 * more of it than this document knows.
 *
 * A resource's attributes and relationships are `required` in its read schema, which a response to
 * a sparse fieldset legitimately breaks. So a request carrying `fields[…]` is validated against a
 * copy of the document with those lists removed, and every member that is present is still checked.
 */
final class OpenApiValidator implements ResponseValidator
{
    private const string ID = 'https://openapi.invalid/document.json';

    private const string SPARSE_ID = 'https://openapi.invalid/sparse.json';

    private readonly Validator $validator;

    /**
     * Each path template as a pattern, longest literal prefix first, so `/albums/-actions/export`
     * wins over `/albums/{id}`.
     *
     * @var array<string, string> pattern => template
     */
    private readonly array $templates;

    /**
     * @throws \LogicException when opis/json-schema is not installed
     */
    public function __construct(private readonly object $document, int $maxErrors = 10)
    {
        if (!\class_exists(Validator::class)) {
            throw new \LogicException('Response validation needs opis/json-schema. Install it with `composer require opis/json-schema`.');
        }

        $this->validator = new Validator();
        $this->validator->setMaxErrors($maxErrors);
        $this->validator->resolver()?->registerRaw($document, self::ID);
        $this->validator->resolver()?->registerRaw(self::sparse($document), self::SPARSE_ID);

        $templates = [];
        foreach (\array_keys((array) ($document->paths ?? [])) as $template) {
            $template = (string) $template;
            $pattern = '#^' . \preg_replace_callback(
                '/\{[^}]+\}|[^{]+/',
                static fn(array $m): string => $m[0][0] === '{' ? '[^/]+' : \preg_quote($m[0], '#'),
                $template,
            ) . '$#';
            $templates[$pattern] = $template;
        }
        \uasort($templates, static fn(string $a, string $b): int => \strlen((string) \strstr($b . '{', '{', true)) <=> \strlen((string) \strstr($a . '{', '{', true)) ?: \strcmp($a, $b));
        $this->templates = $templates;
    }

    /**
     * @throws \InvalidArgumentException when the file cannot be read or is not a JSON object
     * @throws \LogicException           when opis/json-schema is not installed
     */
    public static function fromFile(string $path): self
    {
        $raw = @\file_get_contents($path);
        if ($raw === false) {
            throw new \InvalidArgumentException(\sprintf('Could not read the OpenAPI document %s.', $path));
        }

        return self::fromJson($raw);
    }

    /**
     * @throws \InvalidArgumentException when the document is not a JSON object
     * @throws \LogicException           when opis/json-schema is not installed
     */
    public static function fromJson(string $json): self
    {
        try {
            $document = \json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException('The OpenAPI document is not valid JSON: ' . $e->getMessage(), 0, $e);
        }

        if (!$document instanceof \stdClass) {
            throw new \InvalidArgumentException('The OpenAPI document is not a JSON object.');
        }

        return new self($document);
    }

    public function validate(RequestInterface $request, string $path, int $status, string $contentType, string $body): void
    {
        if (\trim($body) === '') {
            return;
        }

        $pointer = $this->schemaPointer(\strtolower($request->getMethod()), $path, $status, $contentType);
        if ($pointer === null) {
            return;
        }

        try {
            $data = \json_decode($body, false, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return;
        }

        $sparse = \str_contains(\rawurldecode($request->getUri()->getQuery()), 'fields[');
        $result = $this->validator->validate($data, (object) ['$ref' => ($sparse ? self::SPARSE_ID : self::ID) . '#' . $pointer]);
        $error = $result->error();

        if ($error === null) {
            return;
        }

        /** @var array<string, list<string>> $violations */
        $violations = (new ErrorFormatter())->format($error);

        throw new SchemaViolation($request->getMethod(), $path, $status, $violations);
    }

    /**
     * The JSON pointer, percent-encoded for a URI fragment, to the schema of one response.
     */
    private function schemaPointer(string $method, string $path, int $status, string $contentType): ?string
    {
        $template = null;
        foreach ($this->templates as $pattern => $candidate) {
            if (\preg_match($pattern, $path) === 1) {
                $template = $candidate;
                break;
            }
        }
        if ($template === null) {
            return null;
        }

        $operation = $this->document->paths->{$template}->{$method} ?? null;
        $responses = \is_object($operation) ? ($operation->responses ?? null) : null;
        if (!\is_object($responses)) {
            return null;
        }

        $base = '/paths/' . self::escape($template) . '/' . $method . '/responses/';
        foreach ([(string) $status, \intdiv($status, 100) . 'XX', 'default'] as $key) {
            if (!isset($responses->{$key})) {
                continue;
            }

            $response = $responses->{$key};
            $at = $base . self::escape($key);
            $ref = \is_object($response) ? ($response->{'$ref'} ?? null) : null;
            if (\is_string($ref) && \str_starts_with($ref, '#/')) {
                $at = \substr($ref, 1);
                $response = $this->resolve($at);
            }

            return \is_object($response) ? $this->mediaPointer($response, $at, $contentType) : null;
        }

        return null;
    }

    private function mediaPointer(object $response, string $at, string $contentType): ?string
    {
        $content = $response->content ?? null;
        if (!\is_object($content)) {
            return null;
        }

        $base = \strtolower(\trim(\explode(';', $contentType)[0]));
        $chosen = null;
        foreach (\array_keys((array) $content) as $media) {
            $media = (string) $media;
            if (\strcasecmp($media, $contentType) === 0) {
                $chosen = $media;
                break;
            }
            if ($chosen === null && \strtolower(\trim(\explode(';', $media)[0])) === $base) {
                $chosen = $media;
            }
        }

        if ($chosen === null || !isset($content->{$chosen}->schema)) {
            return null;
        }

        $pointer = $at . '/content/' . self::escape($chosen) . '/schema';

        return \implode('/', \array_map(static fn(string $segment): string => \rawurlencode(\rawurldecode($segment)), \explode('/', $pointer)));
    }

    private function resolve(string $pointer): mixed
    {
        $node = $this->document;
        foreach (\array_slice(\explode('/', $pointer), 1) as $segment) {
            $segment = \str_replace(['~1', '~0'], ['/', '~'], $segment);
            if (!\is_object($node) || !isset($node->{$segment})) {
                return null;
            }
            $node = $node->{$segment};
        }

        return $node;
    }

    private static function escape(string $segment): string
    {
        return \str_replace(['~', '/'], ['~0', '~1'], $segment);
    }

    /**
     * A copy of the document with every resource object's `attributes` and `relationships`
     * stripped of `required`, so a sparse fieldset's response still validates member by member.
     */
    private static function sparse(object $document): object
    {
        $copy = \json_decode(\json_encode($document, \JSON_THROW_ON_ERROR), false, 512, \JSON_THROW_ON_ERROR);
        if (!\is_object($copy)) {
            return $document;
        }

        $relax = static function (mixed $schema) use ($copy): void {
            if (!\is_object($schema)) {
                return;
            }
            $ref = $schema->{'$ref'} ?? null;
            if (\is_string($ref) && \str_starts_with($ref, '#/')) {
                $schema = $copy;
                foreach (\array_slice(\explode('/', $ref), 1) as $segment) {
                    $segment = \str_replace(['~1', '~0'], ['/', '~'], $segment);
                    if (!\is_object($schema) || !isset($schema->{$segment})) {
                        return;
                    }
                    $schema = $schema->{$segment};
                }
            }
            if (\is_object($schema)) {
                unset($schema->required);
            }
        };

        $walk = static function (mixed $node) use (&$walk, $relax): void {
            if (\is_array($node)) {
                foreach ($node as $child) {
                    $walk($child);
                }

                return;
            }
            if (!\is_object($node)) {
                return;
            }
            $properties = $node->properties ?? null;
            if (\is_object($properties)) {
                $relax($properties->attributes ?? null);
                $relax($properties->relationships ?? null);
            }
            foreach (\get_object_vars($node) as $child) {
                $walk($child);
            }
        };
        $walk($copy);

        return $copy;
    }
}
