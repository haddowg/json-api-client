<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Examples\MusicCatalog\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * The music catalog's own OpenAPI document, answering as a server.
 *
 * Every operation in the document carries a worked example response, so this PSR-18 client
 * finds the operation a request addresses and answers with that example: the status, the media
 * type and the body the document declares. It does just enough on top to behave like the real
 * thing for the example app: the id in the path becomes the resource's id, an `include` is
 * answered with `included` resources drawn from each type's own single-resource example, a sparse
 * fieldset is applied, and an atomic batch gets one result per operation.
 *
 * It is not a server. Nothing is stored, and a filter or sort is accepted and ignored; the point
 * is that the generated client runs end to end against exactly the documents its server
 * describes, with no server to run.
 */
final class ExampleServer implements ClientInterface
{
    private const string JSON_API = 'application/vnd.api+json';

    /**
     * @var list<RequestInterface>
     */
    public array $requests = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private readonly array $paths;

    /**
     * @param array<string, mixed> $document the decoded OpenAPI document
     */
    public function __construct(
        array $document,
        private readonly ResponseFactoryInterface $responses,
        private readonly StreamFactoryInterface $streams,
    ) {
        $paths = $document['paths'] ?? [];
        /** @var array<string, array<string, mixed>> $paths */
        $this->paths = \is_array($paths) ? $paths : [];
    }

    public static function fromFile(string $path, ResponseFactoryInterface $responses, StreamFactoryInterface $streams): self
    {
        /** @var array<string, mixed> $document */
        $document = \json_decode((string) \file_get_contents($path), true, 512, \JSON_THROW_ON_ERROR);

        return new self($document, $responses, $streams);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        $method = \strtolower($request->getMethod());
        $path = $request->getUri()->getPath();

        if ($path === '/operations' && $method === 'post') {
            return $this->atomic((string) $request->getBody());
        }

        [$template, $id] = $this->route($path);
        $answer = $template === null ? null : $this->example($template, $method);

        if ($answer === null) {
            return $this->json(404, ['errors' => [['status' => '404', 'code' => 'RESOURCE_NOT_FOUND', 'title' => 'Not Found', 'detail' => \sprintf('Nothing answers %s %s.', \strtoupper($method), $path)]]]);
        }

        [$status, $mediaType, $body] = $answer;
        if (!\is_array($body)) {
            return $this->respond($status, $mediaType, \is_string($body) ? $body : '');
        }

        \parse_str($request->getUri()->getQuery(), $query);
        if ($id !== null && \is_array($body['data'] ?? null) && !\array_is_list($body['data'])) {
            $body['data']['id'] = $id;
        }
        $body = $this->project($body, $query);

        return $this->json($status, $body, $mediaType);
    }

    /**
     * The path template a request path addresses, literal templates first, and the `{id}` it
     * carries.
     *
     * @return array{string|null, string|null}
     */
    private function route(string $path): array
    {
        $templates = \array_keys($this->paths);
        \usort($templates, static fn(string $a, string $b): int => \substr_count($a, '{') <=> \substr_count($b, '{'));

        foreach ($templates as $template) {
            $pattern = '#^' . \preg_replace('/\\\{[^}]+\\\}/', '([^/]+)', \preg_quote($template, '#')) . '$#';
            if (\preg_match($pattern, $path, $m) === 1) {
                return [$template, $m[1] ?? null];
            }
        }

        return [null, null];
    }

    /**
     * The first successful response the operation documents with an example.
     *
     * @return array{int, string, mixed}|null
     */
    private function example(string $template, string $method): ?array
    {
        $operation = $this->paths[$template][$method] ?? null;
        $responses = \is_array($operation) ? ($operation['responses'] ?? []) : [];

        foreach (\is_array($responses) ? $responses : [] as $status => $response) {
            $status = (int) $status;
            if ($status < 200 || $status > 299 || !\is_array($response)) {
                continue;
            }

            $content = $response['content'] ?? null;
            if (!\is_array($content) || $content === []) {
                return [$status, self::JSON_API, ''];
            }

            foreach ($content as $mediaType => $media) {
                if (\is_array($media) && \array_key_exists('example', $media)) {
                    return [$status, (string) $mediaType, $media['example']];
                }
            }
        }

        return null;
    }

    /**
     * Answer `include` from each related type's own example, and apply sparse fieldsets.
     *
     * @param array<mixed>        $body
     * @param array<mixed, mixed> $query
     *
     * @return array<mixed>
     */
    private function project(array $body, array $query): array
    {
        $data = $body['data'] ?? null;
        $primary = \is_array($data) ? (\array_is_list($data) ? $data : [$data]) : [];
        $include = \is_string($query['include'] ?? null) ? \array_filter(\explode(',', $query['include'])) : [];

        $included = [];
        foreach ($include as $relation) {
            foreach ($primary as $resource) {
                $relationships = \is_array($resource) ? ($resource['relationships'] ?? null) : null;
                $relationship = \is_array($relationships) ? ($relationships[$relation] ?? null) : null;
                $linkage = \is_array($relationship) ? ($relationship['data'] ?? null) : null;
                foreach (\is_array($linkage) ? (\array_is_list($linkage) ? $linkage : [$linkage]) : [] as $identifier) {
                    $type = \is_array($identifier) ? ($identifier['type'] ?? null) : null;
                    $id = \is_array($identifier) ? ($identifier['id'] ?? null) : null;
                    $related = \is_string($type) && \is_string($id) ? $this->resource($type, $id) : null;
                    if ($related !== null) {
                        $included[$type . ':' . $id] = $related;
                    }
                }
            }
        }
        if ($included !== []) {
            $body['included'] = \array_values($included);
        }

        $fields = $query['fields'] ?? [];
        if (\is_array($fields) && $fields !== []) {
            $body = self::sparse($body, $fields);
        }

        return $body;
    }

    /**
     * One resource object, taken from its type's single-resource example under the given id.
     *
     * @return array<string, mixed>|null
     */
    private function resource(string $type, string $id): ?array
    {
        $example = $this->example('/' . $type . '/{id}', 'get');
        $body = $example === null ? null : $example[2];
        $data = \is_array($body) ? ($body['data'] ?? null) : null;
        if (!\is_array($data)) {
            return null;
        }

        $resource = [];
        foreach ($data as $key => $value) {
            $resource[(string) $key] = $value;
        }

        return [...$resource, 'type' => $type, 'id' => $id];
    }

    /**
     * @param array<mixed>        $body
     * @param array<mixed, mixed> $fields
     *
     * @return array<mixed>
     */
    private static function sparse(array $body, array $fields): array
    {
        $trim = static function (mixed $resource) use ($fields): mixed {
            if (!\is_array($resource) || !\is_string($resource['type'] ?? null) || !\is_string($fields[$resource['type']] ?? null)) {
                return $resource;
            }
            $keep = \explode(',', $fields[$resource['type']]);
            foreach (['attributes', 'relationships'] as $member) {
                if (\is_array($resource[$member] ?? null)) {
                    $resource[$member] = \array_intersect_key($resource[$member], \array_flip($keep));
                }
            }

            return $resource;
        };

        if (\is_array($body['data'] ?? null)) {
            $body['data'] = \array_is_list($body['data']) ? \array_map($trim, $body['data']) : $trim($body['data']);
        }
        if (\is_array($body['included'] ?? null)) {
            $body['included'] = \array_map($trim, $body['included']);
        }

        return $body;
    }

    /**
     * Answer an atomic batch with one result per operation: a create or update gets its type's
     * example resource, with a server id standing in for a create's local id, and a remove nothing.
     */
    private function atomic(string $body): ResponseInterface
    {
        $decoded = \json_decode($body, true);
        $operations = \is_array($decoded) ? ($decoded['atomic:operations'] ?? []) : [];
        $results = [];
        $next = 100;

        foreach (\is_array($operations) ? $operations : [] as $operation) {
            $data = \is_array($operation) ? ($operation['data'] ?? null) : null;
            if (!\is_array($operation) || ($operation['op'] ?? null) === 'remove' || !\is_array($data)) {
                $results[] = new \stdClass();
                continue;
            }

            $id = \is_string($data['id'] ?? null) ? $data['id'] : (string) $next++;
            $resource = \is_string($data['type'] ?? null) ? $this->resource($data['type'], $id) : null;
            if ($resource !== null && \is_array($data['attributes'] ?? null)) {
                $resource['attributes'] = [...(\is_array($resource['attributes'] ?? null) ? $resource['attributes'] : []), ...$data['attributes']];
            }
            $results[] = $resource === null ? new \stdClass() : ['data' => $resource];
        }

        return $this->json(200, ['atomic:results' => $results], self::JSON_API . '; ext="https://jsonapi.org/ext/atomic"');
    }

    /**
     * @param array<mixed> $body
     */
    private function json(int $status, array $body, string $mediaType = self::JSON_API): ResponseInterface
    {
        return $this->respond($status, $mediaType, \json_encode($body, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES));
    }

    private function respond(int $status, string $mediaType, string $body): ResponseInterface
    {
        return $this->responses->createResponse($status)
            ->withHeader('Content-Type', $mediaType)
            ->withBody($this->streams->createStream($body));
    }
}
