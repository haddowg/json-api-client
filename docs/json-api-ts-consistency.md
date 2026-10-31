# Consistency with json-api-ts

The PHP client and [`json-api-ts`](https://github.com/haddowg/json-api-ts) are kept deliberately
consistent. Every divergence is either recorded here with its reason or raised as an issue
against the TypeScript repository.

## Deliberate divergences

| PHP | TypeScript | Why |
|---|---|---|
| `date-time` and `number` coerce to `DateTimeImmutable` and `float` | ISO strings pass through | JavaScript's `Date` is a serialisation hazard; PHP's is not |
| Reserved accessors take a leading `_` | They take a leading `$` | Same spec clause, different identifier rules |
| Writes accept three input forms | Writes take flat input | PHP has no structural typing, so the forms have different guarantees |
| A typed exception per status and per code | One `JsonApiError` with matchers | PHP handles errors by catching types |
| `atomic()` and other absent capabilities are absent methods | Some are threaded as descriptors and throw at run time | A disabled endpoint called from code that compiled is a 404 at run time; an absent method is a static error |
| A paginator menu's picker is `pageBy<Kind>()` | Picker naming follows TypeScript conventions | Picker naming is per language; the rules for reading `x-paginator` are shared |
| `$accept` is the last parameter and `$contentType` the first | Both are named options | A PHP signature has no options bag, and a conditional type needs a real parameter to key on |
| A binary body is a PSR-7 `StreamInterface`; a JSON object body a generated value class | A `Blob` or `ArrayBuffer`; the schema's type | Each uses its platform's native types |
| A form body is a field array with `FilePart` files, encoded by the runtime | A `FormData`, encoded by the transport | PHP has no `FormData` |
| Declared links read through `_declaredLinks()` beside the raw `_links()` | Read on the links object | A PHP subclass cannot narrow `_links()`'s array to an object |
| A link onto another origin is never followed | `allowCrossOriginLinks` opts in | Per-request headers go on every request; a second client with its own base URL serves the other origin |

## To raise against json-api-ts

- **Relationship Queries are not offered.** The PHP client filters and sorts an included to-many
  through `withX()`'s closure under the Relationship Queries profile; the TypeScript client has no
  way to send `relatedQuery`.
- **Relationship counts are untyped.** They are read as `$rel(name).meta.total`; a typed accessor
  would match the PHP client's `_rel('tracks')->total()`.
- **Typed action meta results**, once the server publishes an action's meta schema (see
  [server prerequisites](server-prerequisites.md)).
- **Literal readers that a projector tidy-up would break.** `okResponseRef` reads
  `responses['200']` literally, so moving success responses into `components.responses` would
  silently drop collection paths, paginators and actions; every query-parameter reader matches
  `param.name` directly, so moving parameters into `components.parameters` would silently drop
  pagination, `include`, `sort`, `filter[…]` and `withCount`.
- **Drop `--schemas`.** The served schema bundle is the OpenAPI components dereferenced, so the
  TypeScript codegen can derive its schemas artifact from `--input`, removing an input, a fetch and
  a drift surface.
