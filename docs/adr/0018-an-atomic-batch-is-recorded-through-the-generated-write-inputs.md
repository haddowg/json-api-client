---
status: accepted
---

# An atomic batch is recorded through the generated write inputs

One runtime recorder, `Tx`, takes every type's writes, so the generated `<Type>Create` and `<Type>Update` inputs implement the runtime's `CreateInput<T>`/`UpdateInput<T>` and carry a `_hydrate()` that reads their own result. That is what types a batch end to end: `$tx->create(Album::create()…)` returns a `Created<AlbumBase>`, and `atomic()` returns whatever its callback returned.

## Considered options

- **A generated recorder per API**, with one method per type (`$tx->createAlbum(…)`), needs no interface, but doubles the write surface every type already has and puts the type in the method name rather than the input.
- **Arrays with a `type` discriminant**, as the TypeScript client takes them, cannot be typed per type in PHP: a union of array shapes accepts anything (ADR 0013).
- **Mapping the callback's returned handles to their results**, as the TypeScript client does with a tuple type, cannot be expressed in PHPStan; returning the handles and reading each one's `resource()` keeps every type exact.

## Consequences

A relation setter accepts a `Created` handle of the related type only when the server advertises the extension, since a handle can only come from `atomic()` (ADR 0011).
