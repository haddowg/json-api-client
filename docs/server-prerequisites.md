# Server prerequisites

The generated client is only as complete as the OpenAPI document it is generated from. This page
lists what the codegen reads from a `haddowg/json-api` document, and the gaps that still limit
what the client can type.

## What the document must carry

- **Resource schemas** with `properties.type.const`, from which every type, attribute, relation
  and write shape is derived. Read-only fields are left off create and update inputs, and a pivot
  relation's write schema is separate from the pivot it renders.
- **The error catalogue**: named `<Code>Error` schemas with `code` and `status` constants, wired
  into each error response through an `anyOf` that keeps the generic `Error` open. This is a hard
  prerequisite for code-specific exception classes; without it only the status classes exist.
- **`x-paginator`** on each page schema, naming the strategy and the key that fills each role.
- **Filter value schemas**, so `whereX()` and `filter()` are typed. A filter with no derivable type
  is typed `mixed`, and regenerating against a richer document tightens it with no client change.
- **`x-profile`** on each profile-gated parameter, which drives automatic profile negotiation, and
  the atomic `ext` media type on the operations endpoint.
- **`include` and `fields`** on every operation whose success response carries a resource
  document, writes included. The write projection surface is generated only from what the
  document declares, never from knowledge of the server's source.
- **`info.x-generator.contract`**, a single integer the server bumps whenever the emitted
  structure changes. The codegen supports a range: above it, it warns that newer capabilities were
  not generated; below it, it errors. Package versions are the wrong test, because a release may
  or may not move the structure. A list of feature tokens was rejected, because it would be two
  hand-maintained lists that must agree.

The JSON:API version and the supported profiles and extensions are read from the `JsonApi`
schema, not duplicated elsewhere.

## Open gaps

| Gap | Effect on the client |
|---|---|
| **A per-code error `meta` schema.** The catalogue declares no context shape, so `Error.meta` is a bare object. | Code classes carry no typed context properties (`$e->filterParam`). |
| **An action's meta payload schema.** A meta-output action responds with the generic `MetaDocument`. | A meta result is `array<string, mixed>` rather than a typed result class. |
| **A response-side `profile` declaration** on media types (optional). | None; the client verifies applied profiles through `_document()->jsonapi()->profile`. |

These are not server gaps and should not be raised again:

- Pivot writability is already expressed through the separate write schema and `readOnly`.
- The served JSON Schemas bundle is the OpenAPI components dereferenced, so the codegen derives
  both types and validation from the OpenAPI document alone.
