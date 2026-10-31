---
status: accepted
---

# A conditional on a query builder never narrows the projection

`when()`, `unless()` and `tap()` come in three flavours because a runtime condition cannot produce a compile-time projection. On a query builder the callback's return is discarded and the result stays `self<TProj>`, so a conditionally included relation stays un-narrowed and is reached through `artistRef()`/`hasArtist()` or after a runtime check. On a mutable write builder the callback may return `static|null`; on an immutable write input it must return `static`, since a callback that calls a setter and returns nothing would drop the change.

## Considered options

Marking the query template `@template-covariant` makes narrowing inside the closure type-check, but `TProj` then sits in the callback's parameter, a contravariant position, which is soundness debt that breaks on the first method accepting a `self<TProj>`.
