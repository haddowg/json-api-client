<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient;

use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Support\Wire;

/**
 * The trimmed top-level document: everything a response says about itself rather than about
 * a resource.
 *
 * One instance is built per response and shared by reference with every resource materialised
 * from it, so `$album->_document()` and `$album->artist->_document()` are the same object. It
 * deliberately does not carry `data` or `included` — those became the resources you are
 * holding.
 */
final class Document
{
    /**
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $links
     */
    private function __construct(
        private readonly JsonApi $jsonapi,
        private readonly array $meta,
        private readonly array $links,
    ) {}

    /**
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $links
     */
    public static function of(?JsonApi $jsonapi = null, array $meta = [], array $links = []): self
    {
        return new self($jsonapi ?? JsonApi::absent(), $meta, $links);
    }

    /**
     * Read the top-level members off a decoded response body.
     *
     * @param array<string, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        $jsonapi = Wire::map($raw, 'jsonapi');

        return new self(
            $jsonapi === [] ? JsonApi::absent() : JsonApi::fromArray($jsonapi),
            Wire::map($raw, 'meta'),
            Wire::map($raw, 'links'),
        );
    }

    /**
     * @throws MalformedDocument when the body is not a JSON object
     */
    public static function fromJson(string $body): self
    {
        $decoded = Wire::decode($body);

        if ($decoded === null) {
            throw MalformedDocument::notAnObject();
        }

        return self::fromArray($decoded);
    }

    /**
     * Never null: a response that carried no `jsonapi` member reads as an empty one, so
     * `$album->_document()->jsonapi()->version` is always safe to write.
     */
    public function jsonapi(): JsonApi
    {
        return $this->jsonapi;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * @return array<string, mixed>
     */
    public function links(): array
    {
        return $this->links;
    }

    /**
     * One link by relation, resolved through the object form JSON:API also allows
     * (`{"href": "…"}`).
     */
    public function link(string $relation): ?string
    {
        return Wire::href($this->links[$relation] ?? null);
    }

    /**
     * The document's own address.
     */
    public function self(): ?string
    {
        return $this->link('self');
    }

    /**
     * The `meta.page` block a paginated collection carries, before it is read into a
     * {@see \haddowg\JsonApiClient\Pagination\Page}.
     *
     * @return array<string, mixed>
     */
    public function pageMeta(): array
    {
        return Wire::map($this->meta, 'page');
    }
}
