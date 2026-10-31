---
status: accepted
---

# Reserved members use a leading underscore

Generated DTOs expose API fields as plainly named accessors, and JSON:API forbids only `type` and `id` as field names, so a client accessor named `meta()` or `links()` can clash with a real attribute. Every accessor the client reserves takes a leading underscore (`_self()`, `_meta()`, `_links()`, `_rel()`, `_page()`, `_document()`, …), which is safe by specification: a JSON:API member name must begin with an alphanumeric character. It is the PHP port of the TypeScript client's `$` prefix, which rests on the same clause.

## Considered options

Reserving a handful of plain names with build-time collision detection reads better (`$album->meta()`), but it makes the read surface's safety depend on each API's attribute names, and every new reserved accessor would be a potential breaking change for someone's schema. The `create`/`update` static factories are the remaining plain names, so the codegen still detects attributes with those names and routes them through a fallback accessor.
