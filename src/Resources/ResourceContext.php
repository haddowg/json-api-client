<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Resources;

use haddowg\JsonApiClient\Document;
use haddowg\JsonApiClient\Exceptions\MalformedDocument;
use haddowg\JsonApiClient\Query\ReadQuery;
use haddowg\JsonApiClient\Support\Identifier;
use haddowg\JsonApiClient\Support\Wire;

/**
 * One response, indexed so a resource can find what it links to.
 *
 * Two questions live here, and neither can be answered by a resource object on its own.
 * *Was this relation hydrated?* is answered from the include paths the request actually sent,
 * not from whether linkage happens to resolve — `withCount=tracks` returns a count with no
 * `included` entry, and that must still read as un-included rather than as an empty relation.
 * *Where is the resource this identifier points at?* is answered from an index over `included`
 * and the primary data together, because a compound document does not repeat the primary
 * resource in `included` even when something links back to it.
 *
 * The path is what makes depth work. A context at `tracks` answers `isIncluded('album')` by
 * asking about `tracks.album`, so the runtime backstop is exact at every depth while the static
 * narrowing stops at one.
 */
final class ResourceContext
{
    /**
     * @param array<string, array<string, mixed>> $index resource objects by `type:id`
     */
    private function __construct(
        private readonly Document $document,
        private readonly array $index,
        private readonly mixed $data,
        private readonly ReadQuery $query,
        private readonly string $path,
    ) {}

    /**
     * Index a decoded response body against the read that produced it.
     *
     * @param array<string, mixed> $raw
     */
    public static function of(array $raw, ReadQuery $query = new ReadQuery()): self
    {
        $data = $raw['data'] ?? null;
        $index = [];

        foreach (self::resourceObjects($data) as $resource) {
            $index[self::key($resource)] = $resource;
        }

        $included = $raw['included'] ?? null;

        if (\is_array($included)) {
            foreach (self::resourceObjects($included) as $resource) {
                $index[self::key($resource)] = $resource;
            }
        }

        return new self(Document::fromArray($raw), $index, $data, $query, '');
    }

    /**
     * Parse a response body into a context.
     *
     * @throws MalformedDocument when the body is not a JSON object
     */
    public static function fromJson(string $body, ReadQuery $query = new ReadQuery()): self
    {
        $decoded = Wire::decode($body);

        if ($decoded === null) {
            throw MalformedDocument::notAnObject();
        }

        return self::of($decoded, $query);
    }

    /**
     * The same response, read from one relation deeper.
     */
    public function at(string $relation): self
    {
        return new self(
            $this->document,
            $this->index,
            $this->data,
            $this->query,
            $this->path === '' ? $relation : $this->path . '.' . $relation,
        );
    }

    public function document(): Document
    {
        return $this->document;
    }

    public function query(): ReadQuery
    {
        return $this->query;
    }

    /**
     * The include path a relation of a resource in this context sits at.
     */
    public function pathTo(string $relation): string
    {
        return $this->path === '' ? $relation : $this->path . '.' . $relation;
    }

    public function isIncluded(string $relation): bool
    {
        return \in_array($this->pathTo($relation), $this->query->include, true);
    }

    /**
     * The sparse fieldset this read sent for a type, or null when it sent none.
     *
     * @return list<string>|null
     */
    public function fieldset(string $type): ?array
    {
        return $this->query->fields[$type] ?? null;
    }

    /**
     * The resource object an identifier points at, or null when the response did not carry it.
     *
     * @return array<string, mixed>|null
     */
    public function resolve(Identifier $identifier): ?array
    {
        return $this->index[$identifier->type . ':' . $identifier->id] ?? null;
    }

    /**
     * The document's primary resource object, or null for a to-one relationship that is empty.
     *
     * @return array<string, mixed>|null
     *
     * @throws MalformedDocument when `data` is a collection, or is not a resource object
     */
    public function primary(): ?array
    {
        if ($this->data === null) {
            return null;
        }

        if (!\is_array($this->data) || \array_is_list($this->data)) {
            throw MalformedDocument::unexpectedMember('The document', 'data', 'a resource object');
        }

        /** @var array<string, mixed> */
        return $this->data;
    }

    /**
     * The document's primary resource objects.
     *
     * @return list<array<string, mixed>>
     *
     * @throws MalformedDocument when `data` is not a collection
     */
    public function primaryList(): array
    {
        if (!\is_array($this->data) || !\array_is_list($this->data)) {
            throw MalformedDocument::unexpectedMember('The document', 'data', 'a collection of resource objects');
        }

        return self::resourceObjects($this->data);
    }

    /**
     * Materialise one resource object through the factory a generated class supplies.
     *
     * The declared return is `object` rather than whatever the factory produces, and that is the
     * point of the method. A generated read is typed as the projection it narrowed to, which is
     * a template parameter, and no bound can prove the concrete DTO satisfies it — the runtime
     * always builds an `Album`, while the static type is a narrowing over `AlbumBase`. Erasing
     * here gives that unavoidable assertion one documented home, instead of leaving each emitter
     * to find its own way past `varTag.nativeType`.
     *
     * @param array<string, mixed>                         $raw
     * @param callable(array<string, mixed>, self): object $factory
     */
    public function hydrate(array $raw, callable $factory): object
    {
        return $factory($raw, $this);
    }

    /**
     * Every resource object in a `data` or `included` member, whatever shape it arrived in.
     *
     * @return list<array<string, mixed>>
     */
    private static function resourceObjects(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        if (!\array_is_list($value)) {
            /** @var array<string, mixed> $value */
            return [$value];
        }

        $out = [];

        foreach ($value as $member) {
            if (\is_array($member) && !\array_is_list($member)) {
                /** @var array<string, mixed> $member */
                $out[] = $member;
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $resource
     */
    private static function key(array $resource): string
    {
        $identifier = Identifier::fromArray($resource, 'a resource object');

        return $identifier->type . ':' . $identifier->id;
    }
}
