---
status: accepted
---

# Reserved members use a leading underscore

Generated resource DTOs expose user-named fields as plainly-named accessors, so every
name the client reserves for itself competes with an attribute or relation name. JSON:API
forbids only `type` and `id` as field names, so `meta`, `links` and `self` are all legal
attribute names and a naive `$album->meta()` is a real clash. We prefix every reserved
accessor with `_` instead: `_self()`, `_meta()`, `_links()`, `_raw()`, `_rel()`, `_edge()`,
`_pivot()`, `_document()`, `_page()`, `_next()`, `_autoPaging()`.

This is safe by specification, not by convention. The JSON:API 1.1 schema defines a member
name as `^[a-zA-Z0-9]{1}(?:[-\w]*[a-zA-Z0-9])?$` — it must begin and end with an
alphanumeric character — so no member name can ever begin with `_`. The suite already
leans on this clause: the server's reserved `?withCount=_self_` token is legal precisely
because `_self_` cannot collide with a real field.

It is also the direct port of the TypeScript client's reasoning rather than its syntax.
That client prefixes envelope accessors with `$` because JSON:API forbids `$` in member
names; PHP identifiers cannot contain `$`, but a leading underscore rests on the same
guarantee in the same clause.

## Considered options

Reserving ~8 plain names and detecting collisions at build time was the alternative. It
reads better in isolation (`$album->meta()`), but it makes the safety of the whole read
surface depend on an API's choice of attribute names, and the fallback path it needs for a
colliding attribute is strictly worse than an underscore on the reserved side. We would
also have had no budget left: each new reserved accessor would be a potential breaking
change for somebody's schema.

## Consequences

A leading underscore conventionally signals "private" in PHP, so these public accessors
read oddly at first glance. Documenting the rule once is cheaper than the alternative, and
the prefix has a useful secondary effect: it visually separates the client's own surface
from the API's data at every call site.

Two plain names survive as genuine collision risks — the `create`/`update` static factories
on the resource class, which PHP will not let coexist with instance accessors of the same
name. Build-time detection is still required for those, resolved by routing the colliding
*attribute* through a fallback accessor so the write API stays uniform across types.
