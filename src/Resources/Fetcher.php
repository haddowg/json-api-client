<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Resources;

use haddowg\JsonApiClient\Exceptions\JsonApiErrorResponse;
use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Exceptions\TransportException;
use haddowg\JsonApiClient\Http\Request;
use haddowg\JsonApiClient\Http\Response;
use haddowg\JsonApiClient\Http\Transport;
use haddowg\JsonApiClient\Query\ReadQuery;
use haddowg\JsonApiClient\Support\Wire;
use Psr\Http\Message\StreamInterface;

/**
 * The seam between a generated accessor and the wire.
 *
 * Generated code stays declarative — it knows paths, paginator kinds and profile URIs, and it
 * knows which class to hydrate — so everything that is identical for all thirteen types is
 * here instead of emitted thirteen times. What is deliberately *not* here is anything
 * generic over the resource type: a collection's `PaginatedCollection<Album, PageNumber>` is
 * assembled in generated code, where both parameters are concrete and the template does not
 * have to be threaded through a runtime signature.
 */
final class Fetcher
{
    public function __construct(private readonly Transport $transport) {}

    /**
     * Send a read and index the document it returns.
     *
     * The read query is carried into the context because the include paths are what decide
     * whether a relation reads as hydrated. Following a pagination link reuses the same query
     * for the same reason: the page changed, the projection did not.
     *
     * @throws JsonApiErrorResponse when the server answers with a non-2xx status
     * @throws TransportException   when no response is received at all
     */
    public function read(Request $request, ReadQuery $query = new ReadQuery()): ResourceContext
    {
        return ResourceContext::fromJson($this->transport->send($request)->body, $query);
    }

    /**
     * Follow an absolute URL the server handed back, such as a `next` page link.
     *
     * @throws JsonApiErrorResponse when the server answers with a non-2xx status
     * @throws TransportException   when no response is received at all
     */
    public function readUrl(string $url, ReadQuery $query = new ReadQuery()): ResourceContext
    {
        return $this->read(Request::get($url), $query);
    }

    /**
     * Send a request whose response body is of no interest — a delete, or an action declaring
     * `204`.
     *
     * @throws JsonApiErrorResponse when the server answers with a non-2xx status
     * @throws TransportException   when no response is received at all
     */
    public function send(Request $request): Response
    {
        return $this->transport->send($request);
    }

    /**
     * Send a request answered by a meta-only document and return its top-level `meta`.
     *
     * Untyped by necessity: an action declaring `output: meta` responds with the generic `Meta`
     * schema, so the payload's shape is not in the document and cannot be generated.
     *
     * @return array<string, mixed>
     *
     * @throws JsonApiErrorResponse when the server answers with a non-2xx status
     * @throws TransportException   when no response is received at all
     */
    public function meta(Request $request): array
    {
        $decoded = Wire::decode($this->transport->send($request)->body);

        return $decoded === null ? [] : Wire::map($decoded, 'meta');
    }

    /**
     * Send a request answered by a raw text body, such as a CSV export, and return the body as it
     * came.
     *
     * @throws JsonApiErrorResponse when the server answers with a non-2xx status
     * @throws TransportException   when no response is received at all
     */
    public function text(Request $request): string
    {
        return $this->transport->send($request)->body;
    }

    /**
     * Send a request answered by a raw binary body, such as an image or a PDF, and return the body
     * as a stream.
     *
     * @throws JsonApiErrorResponse when the server answers with a non-2xx status
     * @throws TransportException   when no response is received at all
     */
    public function stream(Request $request): StreamInterface
    {
        return $this->transport->stream($this->transport->send($request)->body);
    }

    /**
     * Send a request answered by a raw JSON body that is not a JSON:API document, and return it
     * decoded, objects as associative arrays. The generated caller narrows it to the body's schema.
     *
     * @throws JsonApiErrorResponse when the server answers with a non-2xx status
     * @throws TransportException   when no response is received at all
     * @throws MalformedDocument    when the body is not JSON
     */
    public function json(Request $request): mixed
    {
        $body = $this->transport->send($request)->body;

        try {
            return \json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw MalformedDocument::invalidJson('The response body', $e);
        }
    }

    /**
     * Send a request answered by a raw JSON object, and return it with string keys for a generated
     * value class to narrow.
     *
     * @return array<string, mixed>
     *
     * @throws JsonApiErrorResponse when the server answers with a non-2xx status
     * @throws TransportException   when no response is received at all
     * @throws MalformedDocument    when the body is not a JSON object
     */
    public function jsonObject(Request $request): array
    {
        $decoded = $this->json($request);

        if (!\is_array($decoded) || ($decoded !== [] && \array_is_list($decoded))) {
            throw MalformedDocument::notAJsonObject('The response body');
        }

        $out = [];
        foreach ($decoded as $key => $value) {
            $out[(string) $key] = $value;
        }

        return $out;
    }
}
