<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Query;

/**
 * What a read returns: which relations come back hydrated, and which fields come back at all.
 *
 * Projection is its own concern because a write response honours `include` and `fields` and
 * nothing else. Under strict query validation a stray `sort` on a `POST` is a `400`, not
 * something the server quietly ignores, so `sort`/`filter`/`page` are not merely unused
 * here — they are absent, and `returning()->sort('title')` is a call to an undefined method.
 * {@see Query} adds them back for reads.
 *
 * `TProj` is the projection the builder has accumulated: the resource's base type, intersected
 * with one marker interface per relation a generated `withX()` has included. That is what makes
 * `$album->artist` type-check only after `->withArtist()`.
 *
 * Generated subclasses narrow by re-declaring the return of each `withX()`, which is why the
 * primitives here return `static` — the subclass survives the call, in either order along the
 * chain.
 *
 * @template TProj of object
 */
abstract class Projection
{
    /**
     * @var list<string>
     */
    protected array $include = [];

    /**
     * @var array<string, list<string>>
     */
    protected array $fields = [];

    /**
     * Include relation paths without narrowing the projection.
     *
     * The door for a deep include (`tracks.album`) and for an include list assembled at
     * runtime. Nothing is narrowed, so the relation is reached through the always-safe
     * companions or after a runtime check; the absence of a relation you asked for is still a
     * loud throw at every depth.
     *
     * @return static
     */
    public function with(string ...$paths): static
    {
        return $this->including(...$paths);
    }

    /**
     * Restrict which fields of a type come back, as a sparse fieldset.
     *
     * An empty list for a type is meaningful and is sent: it selects no members of that type.
     * Attributes stay statically present on the resource either way — reading one a fieldset
     * excluded is a runtime throw naming the field, not a silent null.
     *
     * @param array<string, list<string>> $fields field names by resource type
     *
     * @return static
     */
    public function fields(array $fields): static
    {
        $clone = clone $this;

        foreach ($fields as $type => $names) {
            $clone->fields[$type] = \array_values($names);
        }

        return $clone;
    }

    /**
     * The projection as query parameters.
     */
    public function toReadQuery(): ReadQuery
    {
        return new ReadQuery(include: $this->include, fields: $this->fields);
    }

    /**
     * Add include paths, keeping the builder immutable and the list free of duplicates.
     *
     * The primitive every generated `withX()` delegates to. It returns `static` so the
     * generated method can re-declare the return as `static<TProj&Marker>` and have the
     * narrowing land on the concrete subclass.
     *
     * @return static
     */
    protected function including(string ...$paths): static
    {
        $clone = clone $this;

        foreach ($paths as $path) {
            if (!\in_array($path, $clone->include, true)) {
                $clone->include[] = $path;
            }
        }

        return $clone;
    }
}
