---
status: accepted
---

# Query and projection builders are final classes sharing a trait

A write response accepts only `include` and `fields`, and a stray `sort` on a `POST` is a 400 under strict query validation, so a write takes a projection builder (`AlbumProjection`) carrying only those concerns, while reads use the query builder (`AlbumQuery`). The two do not inherit from each other: `@return static<TProj&Marker>` preserves the subclass only on a `final` class, and level 9 rejects it once `AlbumQuery extends AlbumProjection`. The `withX()` methods live in a generic trait (`@phpstan-require-extends`) used by both final classes.

## Considered options

- **Reusing the query builder for writes** exposes `sort`, `filter` and `page` on a `POST`.
- **A fluent `returning()->withArtist()->create(…)`** adds a second entry point per write, and a projection with no terminal call is a silent no-op.

## Consequences

A write takes its projection as a closure or a prebuilt projection object, and needs a conditional return (`$projection is null ? AlbumBase : AlbumBase&TProj`) because a plain intersection fails to resolve `TProj` when none is passed.
