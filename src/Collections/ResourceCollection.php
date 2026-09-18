<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Collections;

/**
 * A list of resources, with the collection-level members that came with it.
 *
 * PHP cannot attach properties to an array, so where the TypeScript client hands back an
 * augmented array this hands back an object you can still index and iterate. Reserved members
 * take the leading underscore a JSON:API member name can never start with, so nothing here can
 * ever collide with something the API names.
 *
 * This is the unpaginated shape: a collection endpoint whose type declares no paginator, a
 * hydrated to-many relation, linkage, an atomic result. A paginated collection is
 * {@see PaginatedCollection}, which adds `_page()` and navigation — so `_page()` is never null
 * and never throws, because it is absent wherever pagination is meaningless.
 *
 * @template T
 *
 * @implements \IteratorAggregate<int, T>
 * @implements \ArrayAccess<int, T>
 */
class ResourceCollection implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * @param list<T>              $items
     * @param array<string, mixed> $meta  the document's (or relationship object's) `meta`
     * @param array<string, mixed> $links the document's (or relationship object's) `links`
     */
    public function __construct(
        private readonly array $items,
        private readonly array $meta = [],
        private readonly array $links = [],
    ) {}

    /**
     * @return \Traversable<int, T>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return \count($this->items);
    }

    /**
     * @return list<T>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @return T|null
     */
    public function first()
    {
        return $this->items[0] ?? null;
    }

    /**
     * @return T|null
     */
    public function last()
    {
        return $this->items[\count($this->items) - 1] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function _meta(): array
    {
        return $this->meta;
    }

    /**
     * @return array<string, mixed>
     */
    public function _links(): array
    {
        return $this->links;
    }

    public function offsetExists(mixed $offset): bool
    {
        return \is_int($offset) && \array_key_exists($offset, $this->items);
    }

    /**
     * @return T
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (!\is_int($offset) || !\array_key_exists($offset, $this->items)) {
            throw new \OutOfBoundsException(
                \sprintf('No member at index %s; the collection holds %d.', \var_export($offset, true), \count($this->items)),
            );
        }

        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \LogicException('A resource collection is a read result and cannot be modified.');
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \LogicException('A resource collection is a read result and cannot be modified.');
    }
}
