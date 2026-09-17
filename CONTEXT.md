# json-api-client — Context

A PHP client for JSON:API 1.1 services, generated from the service's OpenAPI 3.1
document. The PHP counterpart to `json-api-ts`, consuming APIs built with
`haddowg/json-api` and its Laravel/Symfony adapters.

**Source of truth at design time** is the OpenAPI 3.1 document the server bundle
emits, and the `json-api-ts` design it has already been proven against (see that
repo's `CONTEXT.md`). The client reads the served document; it does not depend on
`haddowg/json-api`.

## Glossary

- **Descriptor** — the runtime map of a type's attributes, relations (cardinality,
  related types), operation paths, paginator kind, and include/sort/filter tokens.
  The `json-api-ts` `resourceMap` equivalent. In PHP it is generated code, not a
  data literal, because PHP cannot derive types from a value.
- **Resource DTO** — the generated class for one resource type. Carries `id`, every
  attribute the schema declares, and every relation. One class per type.
- **Relation marker** — a generated interface per relation (`AlbumHasArtist`),
  implemented by the resource DTO. Intersecting markers into the query builder's
  template parameter is what makes `include` statically checkable.
- **Projection** — the intersection type a query builder produces
  (`AlbumBase&AlbumHasArtist`). What `include` narrows.
- **Selected / included** — an attribute present in the response because no sparse
  fieldset excluded it; a relation hydrated because `include` named it. Absence of
  either is a runtime throw, never a silent null.

## Resolved decisions

### Un-included relations throw; lazy fetch is a later opt-in

Accessing a relation that was not included raises `RelationNotIncludedException`
naming the relation and the fix. Always-safe companions (`artistRef()`,
`hasArtist()`) expose linkage without risking the throw.

**Deferred (stretch, explicitly out of the initial build):** opt-in lazy fetch over
HTTP instead of throwing — configurable globally, per-relation in config, and
switchable at runtime for named relations. Deferred because hidden per-access HTTP
calls inside a loop are the classic way to melt an API.

### Attributes are always on the type; sparse fieldsets are a wire concern only

Every attribute the schema declares is declared on the DTO and is statically
present. A sparse fieldset changes only what arrives on the wire; reading an
excluded attribute throws `FieldNotSelectedException`.

Rejected: narrowing attributes away with one marker interface per attribute. Two
reasons. The interface cost is dominated by attributes (75 attributes vs 12
relations across the music-catalog spec's 9 types — 6× the cost for the weaker
guarantee), and fieldsets are routinely built programmatically, where a
non-literal array degrades to the base type and the static narrowing yields
nothing precisely when it is needed.

### `include` is statically narrowed at depth 1, runtime-checked deeper

Per-relation builder methods narrow: `->withArtist()` intersects `AlbumHasArtist`
into the projection, so `$album->artist` type-checks and `$album->tracks` is a
PHPStan error. Verified working in pure phpdoc generics — no PHPStan extension
needed, in either direction (see ADR 0001).

A deliberately less type-safe `->with(...)` sits alongside it, accepting literal
include tokens *or* fully dynamic values. No narrowing, runtime-checked only. This
is the path for deep includes (`tracks.album`) and programmatic include lists.

Nested hops are declared but not narrowed: `$album->tracks[0]->album` compiles and
throws at runtime if the deep include was omitted. Static checking where it is
cheap, a runtime backstop at every depth.

### PHP 8.3 floor, with an opt-in 8.4+ codegen target

Method accessors (`title(): string`, `artist(): Artist`) are **always** generated
and are the canonical contract — all behaviour lives there, and every docs snippet
uses this form.

On an 8.4+ target the generator *additionally* emits get-only property hooks that
delegate to those methods (`public string $title { get => $this->title(); }`), and
relation markers gain `public Artist $artist { get; }` alongside the method. So
`$album->title` and `$album->artist->name` work on 8.4 without a second
implementation: drift is structurally impossible because a hook is a one-line
delegation. Both forms are live simultaneously on 8.4.

The target defaults to the running `PHP_VERSION` and is overridable. The runtime
package stays `^8.3`; only generated output uses 8.4 syntax.

### Packages — two repos

- `haddowg/json-api-client` (`haddowg\JsonApiClient\`) — runtime, plus both
  framework bridges under `Bridge\Laravel\` / `Bridge\Symfony\`. Framework deps are
  `require-dev` + `suggest`, following `platform-auth-client`'s optional-bundle
  pattern. The bridges are a service provider, a config file, and a console command
  each — too small to earn their own repos, and the server side's cross-repo
  lockstep (laravel's byte-compat job resolving sibling symfony at `main`) is a
  known source of false CI failures worth not reproducing.
- `haddowg/json-api-client-codegen` (`haddowg\JsonApiCodegen\`) — dev-only CLI, so
  the spec parser and emitter never reach a production autoload map. Mirrors the TS
  split of `@haddowg/json-api-client` vs `@haddowg/json-api-codegen`.

### Writes accept three input forms over one canonical DTO

`$client->albums->create($input)` and `->update($input)` always execute and always
return the resource. `$input` is any of:

1. an **array shape** — `['title' => 'Geogaddi']`, typed by a generated
   `@phpstan-type` alias;
2. a **named-argument DTO** — `new AlbumCreate(title: 'Geogaddi')`, the canonical
   internal form. A `Missing` enum sentinel defaults every member, keeping absent
   distinct from an explicit `null` as JSON:API `PATCH` requires;
3. a **fluent builder** — `Album::create()->title('Geogaddi')->artist($boc)`.

The builder's `build()` produces the DTO; the array goes through a generated
`from()` that validates keys against the descriptor. One serialisation path, three
front doors.

Required members take no default, so omitting one is a static error
(`Missing parameter $releasedAt`) as well as an `ArgumentCountError`. Named
arguments mean the declaration's required-before-optional ordering never binds
callers.

Verified coverage at level 9 — no single form catches everything, and the gaps
differ:

| | missing required | typo on required key | typo on **optional** key | wrong value type | absent vs null |
|---|---|---|---|---|---|
| array shape | static | static | **runtime** | static | native |
| named-arg DTO | static | static | static | static | `Missing` sentinel |
| fluent builder | **runtime** | static | static | static | native |

The DTO is the only form with full static coverage, so **docs lead with the DTO**;
the builder is for conditional assembly, the array for programmatic input. Runtime
guards backstop every gap: `from()` rejects unknown keys
(`UnknownAttributeException`, with a did-you-mean) and `build()` rejects a missing
required member.

A typo is invisible only on an *optional* key — a typo'd required key fails because
the real key is then missing. So the blind spot is precisely the all-optional PATCH
shape, which is what the runtime `from()` guard exists for.

**Codegen requirement:** `@phpstan-type` aliases are class-local. Emit them on a
per-type holder class (`AlbumShapes`) and pull them in with `@phpstan-import-type`
wherever they are referenced — without the import the alias silently degrades to
bare `array` and every array-branch check is lost.

### Write builders are static factories on the resource class

`Album::create()` returns a create builder; `Album::create([…])` returns the create
DTO. Same for `update`. The arity overload is expressible in phpdoc
(`@return ($input is null ? AlbumCreateBuilder : AlbumCreate)`).

Deliberately hosted on the resource class rather than the client accessor: both
returns are inert values, so neither overload performs IO and a forgotten submit
cannot silently no-op. One import (`Album`) reaches every write form for that type.

**Codegen must detect collisions.** PHP forbids a static `create()` and an instance
accessor `create()` in one class, so a type with an attribute or relation named
`create`/`update` is a hard clash. Handle it the way the TS codegen handles reserved
verbs: detect at build time, emit a warning, and route the affected member through
a fallback accessor.

### Conditional composition via `when()` / `unless()` / `tap()`

Laravel's `Conditionable` shape, on both builder families, with the callback
parameter typed so autocomplete and checking work inside the closure (verified: a
typo'd setter and a wrong value type inside a `when()` closure are both caught).

Two flavours, and the difference is load-bearing rather than stylistic:

- **Write builders** — shared `Conditionable` trait, `callable(static): (static|null)`,
  return honoured. No narrowing exists here, so `when()` is unambiguous.
- **Query builders** — `callable(self<TProj>): mixed`, return **discarded**, result
  typed `self<TProj>`. A runtime condition cannot produce a compile-time
  projection, so a conditional `withArtist()` must not narrow.

Rejected: marking the query template `@template-covariant`. It works — narrowing
inside the closure type-checks and unconditional narrowing afterwards still resolves
— but `TProj` appears in the callback's parameter position, a contravariant slot, so
covariance is soundness debt that breaks on the first method accepting a
`self<TProj>`.

Consequence, and it is the honest one: a conditionally-included relation stays
un-narrowed, so `$album->artist` is a static error and the relation is reached via
`artistRef()`/`hasArtist()` or after a runtime check. Same lane as `->with(...)`.

Note for the test suite: standalone builder chains whose result is discarded trip
`method.resultUnused`, the same hazard already recorded against discarded `->build()`
calls on the server side. Assert on the result or don't write the chain.

### Reads: collections, pagination, and the three JSON:API levels

Use JSON:API's own vocabulary for the levels, not "envelope": **document** (the
top-level object), **resource object**, **relationship object**, **resource
identifier object**.

**Collections are two classes, not one with nullable pagination.** PHP cannot attach
properties to arrays, so the TS augmented array becomes:

- `ResourceCollection<T>` — `IteratorAggregate<int,T>`, `Countable`, `_meta()`,
  `_links()`. Used for `paginator: "none"` types (3 of 13 in the music-catalog
  spec), hydrated `included` to-many values, linkage, and atomic results.
- `PaginatedCollection<T, TPage of Page>` extends it — `_page(): TPage`, `_next()`,
  `_prev()`, `_autoPaging()` (a lazy `Generator` walking every remaining page).

So `_page()` never returns null and never throws — it does not exist where
pagination is meaningless. `TPage` is the exact paginator kind, so
`$albums->_page()->after` is a static error on a page-paginated type. Relation reads
resolve the relation's own `paginator` override before the related type's, matching
the descriptor's existing rule.

Verified at level 9. One annotation trap: `@return Generator<int, T>` silently drops
`T` — all four of PHPStan's `Generator` template params are required
(`Generator<int, T, mixed, void>`). A second: a `@return` tag must start its own
line in the docblock or it is ignored outright.

### Reserved members take a leading underscore

The TS client prefixes envelope accessors with `$` because JSON:API forbids `$` in
member names. PHP identifiers cannot contain `$`, but the *guarantee* ports: the
spec's own schema (vendored at `json-api/resources/schemas/jsonapi-1.1.json:59`)
defines a member name as `^[a-zA-Z0-9]{1}(?:[-\w]*[a-zA-Z0-9])?$` — it must start
and end alphanumeric, so **a member name can never begin with `_`**.

Leading `_` is therefore a spec-guaranteed private namespace, and every reserved
accessor uses it. No collision detection, no reserved-word budget:

```php
$album->_self();   $album->_meta();   $album->_links();   $album->_raw();
$album->_rel('tracks');                    // links-only relations, introspection
$track->_edge();   $track->_pivot()->position;
$album->_document()->jsonapi()->version;   // and ->ext, ->profile, ->meta()
```

The suite already depends on this clause: the server's reserved `?withCount=_self_`
token is safe for exactly this reason.

`_document()` is shared by reference across every resource from one response, as
`$document` is in TS. `jsonapi` is a typed value object rather than an array,
because `ext` advertises the atomic extension and `profile` carries the Countable
profile the descriptor already references — clients need to assert on both.

**Residual collision:** `Album::create()` / `::update()` are plain names, so an
attribute literally named `create` clashes with the static. Detect at build time and
route the *attribute* through a fallback accessor, keeping the write API uniform —
the same resolution the TS codegen uses for verb-colliding relations.

### Errors: typed exceptions, with code-specific classes generated from the spec

Mirrors core's own class-per-condition style behind a contract, rather than the TS
client's single `JsonApiError` + matchers. Catch-by-type is how PHP handles errors
and core already ships 30+ exception classes; this would have been the only part of
the suite that didn't.

Two layers, and only the second one depends on the server change below:

1. **Status-based hierarchy** (`NotFound`, `ValidationFailed`, `Conflict`, …) under a
   `JsonApiErrorResponse` base implementing a `ClientExceptionInterface` contract
   exposing `statusCode()`, `errors(): list<Error>`, `byPath()`, plus the TS
   client's matchers (`is4xx()`, `isUnprocessable()`, …). Works against any JSON:API
   server. **Not blocked** — also carries transport failures and code-less errors.
2. **Code-specific subclasses, generated per API**, exposing the error's `context`
   as typed properties (`$e->filterParam` rather than a string dug out of `meta`).
   Lands additively when the server change ships; no breaking change.

`byPath()` remaps each error's `source.pointer` to the caller's input path —
`/data/attributes/title` → `title`, `/data/attributes/releaseInfo/label` →
`releaseInfo.label`, `/data/relationships/orderedTracks/data/0/meta/pivot/position`
→ `orderedTracks[0]._pivot.position`. The raw pointer stays on the error as an
escape hatch. Query-side errors use `source.parameter` and are left alone. Atomic
pointers carry an op-index prefix, so they remap to `(opIndex, path)`.

Rejected: a hand-maintained code→exception table. It would have shipped sooner, but
a table that drifts against core's exception set is precisely the failure the suite
is sold against.

### Atomic operations: typed handles, not positional tuples

PHP has no mapped types, so the TS client's trick of mapping a tuple of handles to a
tuple of results cannot port. It does not need to — make the handle the typed thing
and pass the closure's return through generically (`@template T`,
`@param callable(Tx): T`, `@return T`):

```php
[$artist, $album] = $client->atomic(function (Tx $tx) {
    $a = $tx->create(Artist::create()->name('Boards of Canada'));
    $b = $tx->create(Album::create()->title('Geogaddi')->artist($a));
    return [$a, $b];
});
$artist->result()->name;
```

A `create` handle doubles as a `{type, lid}` relationship ref, so relation setters
accept `Identifier|Handle|<Resource>`. Results map to handles by op index. The
atomic `ext` media type is negotiated on both `Content-Type` and `Accept`.

One incidental win over TS: because the builders are type-scoped, `$tx->create()`
needs no `type` discriminant in the payload.

### Framework bridges — v1 scope

In scope:

- **DI wiring, config, codegen command.** Service provider / bundle, a config tree
  for base URL, auth and per-server clients, and `json-api-client:generate` plus a
  `:check` drift gate for CI. The generated client per server is autowirable.
- **Native HTTP client + auth token provider.** The framework's own HTTP stack as
  the PSR-18 transport rather than pulling a second one
  (`symfony/http-client` is PSR-18; Laravel bridges Guzzle via `illuminate/http`),
  with per-request auth resolved through a container-resolved callable. One HTTP
  stack per app, one place for proxy/timeout/TLS.
- **Test fakes + assertions.** A fake transport over recorded fixtures plus
  `assertSent`/`assertNothingSent`, mirroring core's existing `Testing\*` helpers so
  client-side tests read like server-side ones.

Deferred to a future enhancement, explicitly out of the initial build:

- **Response caching + conditional requests** (PSR-6/PSR-16, ETag/`If-None-Match`).
  The nearest analog to what TanStack Query does for the TS client, and valuable for
  server-to-server traffic — but invalidation of a compound document spans its
  `included` resources, and getting that wrong serves stale data silently.

### Codegen output — one class per file, PSR-4, committed

A 13-type API generates roughly 114 classes (13 types × base/concrete/query/shapes,
plus 12 relation markers, plus 9 writable types × two DTOs and two builders, plus
enums and per-type accessors). They are emitted one per file under a configured
namespace, one namespace per server, and **committed** — reviewable, diffable and
versioned, the same principle as the TS client's committed output.

Emission is sorted and stable so a regen diff contains only genuinely-changed
classes. `composer dump-autoload -o` makes the file count irrelevant at runtime.

Rejected: a single ~5,000-line file. It looks like the truest port of the TS
artifact, but that file is one file because a TS module *is* one file, not because
one file was the goal — and in PHP it degrades IDE indexing, review, merge conflicts
and PHPStan cache granularity all at once. Also rejected: generating at install time
into a gitignored directory, which trades committed code for a committed spec and
lets someone else's API break your production build.

### Attribute values coerce to native PHP types

`date`/`date-time`/`time` become `DateTimeImmutable`, `number` becomes `float`,
`integer` becomes `int`, and enumerated strings become generated PHP enums (named
from `x-enum-varnames`, documented from `x-enum-descriptions`).

This is symmetry with the server, not a preference: core's `DateTime` field
"hydrates a string back to a `\DateTimeImmutable`" and its `Decimal` field is "a
floating-point attribute (JSON `type: number`) … serializes/hydrates as `float`".
The client performs the same coercion on the same wire form.

It diverges from the TS client, which passes ISO strings through — deliberately,
because JS `Date` is a serialisation hazard with timezone surprises. Neither
applies to `DateTimeImmutable`, and "properly typed DTOs" was the brief.

Write direction accepts `DateTimeImmutable` in typed signatures; the array form
also takes ISO strings, since it is the loose door by design.

**Caveat worth carrying to the enrichment list:** core's `DateTime` field has a
per-field `$format` (default ATOM), but the OpenAPI document conveys only
`format: date-time`. A server configured with a non-ISO custom format emits strings
the client cannot parse, and nothing in the spec says so. The projector should emit
the format pattern.

### Target is our own server packages, not generic JSON:API

`haddowg/json-api` and its adapters are the client's target. Generic JSON:API
support is a nice-to-have, never a constraint. Our servers expose specific
capabilities through profiles, extensions and a specific OpenAPI structure, and
codegen is expected to detect that structure and light up the full surface.

Consequence for errors: the code catalogue (prerequisite 1) is a **hard
prerequisite**, not an optional enhancement. The status-based hierarchy remains as
the fallback for transport failures, code-less errors, and application-defined codes
the projector never saw.

**No detection layer.** Codegen assumes our structure rather than fingerprinting it,
and there is no degraded generic mode — a second, permanently less-tested code path
for a nice-to-have. Implementation note: the document is read through a typed reader,
so a missing structure surfaces as `expected
components.schemas.AlbumsResource.properties.type.const` rather than a `TypeError`
deep in array access. That is a consequence of not using raw array access, not a
detection feature.

This also demotes prerequisite 4. `info.x-generator` was earning its place as an
identity and version check; with no detection there is nothing to check, and
per-parameter `x-profile` already carries what negotiation needs.

### Profiles and extensions are first-class

The suite ships two profiles (**Countable**, **Relationship Queries**), a
**cursor-pagination** profile, and the standard **Atomic Operations** extension.
Both mechanisms are already machine-readable in the emitted document: `x-profile` on
a parameter names the profile that parameter requires, and the atomic extension is
detectable from the `ext="https://jsonapi.org/ext/atomic"` media-type key.

**Negotiation is automatic and descriptor-driven.** The client adds
`profile="…"` to `Accept` if and only if a request actually uses a profile-gated
parameter. Nothing to configure — `x-profile` already carries the mapping.
Verification comes back through `_document()->jsonapi()->profile`, which is why that
member is a typed value object rather than an array.

**Relationship Queries surfaces as an optional closure on `withX()`.** The closure
shapes the *request*, it does not narrow the projection:

```php
$client->albums->query()
    ->withTracks(fn ($t) => $t->filter(['approved' => true])->sort('-createdAt'))
    ->withArtist()
    ->first();
// include=tracks,artist
// relatedQuery[tracks][filter][approved]=true&relatedQuery[tracks][sort]=-createdAt
// Accept: application/vnd.api+json;profile="…/relationship-queries/"
```

This is deliberately a request-shaping closure only. Nested *narrowing* stays
rejected (ADR 0001), so the closure cannot smuggle back the recursive generic
machinery we declined — the depth-1 rule is unchanged. Rejected alternatives: a
separate `relatedQuery('tracks', …)` call names the relation twice with nothing
tying it to an actual include, and generated per-relation `withTracksWhere(filter:
[…])` methods reintroduce the optional-key typo hole measured in ADR 0003.

### Relationship mutations: generated per-relation methods, typed edge builder

One method per relation on the id-handle, exposing only the verbs the descriptor
permits — a to-one has `set()` and no `add()`, statically. Same principle as
`_page()`: what the server does not permit is not generated.

**Two independent suppression flags, and both must be honoured.** `related: false`
(`withoutRelatedEndpoint()`) suppresses the related-resources read; `relationship:
false` (`withoutRelationshipEndpoint()`) separately suppresses the *linkage* read.
The TS client gates each one; generating either against a switched-off endpoint
produces a runtime 404 from code that compiled.

```php
$pl = $client->playlists->id($id);
$pl->tracks()->add([Track::ref('4')]);
$pl->owner()->set(User::ref('7'));
$pl->owner()->add(…);                       // PHPStan: undefined method
$pl->orderedTracks()->replace([
    Track::ref('4')->pivot(position: 1, weight: 5),
]);
```

Pivot writes go through a generated edge builder carrying **only writable** fields,
with required ones enforced. Not an array shape: pivot payloads are mostly-optional,
which is exactly where an array shape silently swallows a typo (ADR 0003) — and
`positon` compiling while quietly dropping the ordering is a bad failure.

**Pivot writability needs no server change.** The spec already expresses it in
standard OpenAPI — `"addedAt": {…, "readOnly": true}` and `"required": ["position"]`
on the pivot object. The *TS descriptor* flattened that away (`pivotFields` is
name→format with no writability marker, despite its CONTEXT.md stating read-only
pivot fields are excluded from writes), so this is a TS-side descriptor gap worth
reporting, not a projector gap. Reading `readOnly` properly also gets read-only
*attributes* right for free: they appear on the read DTO and not in
`<Type>Create`/`<Type>Update`.

Mutations are also reachable as sugar from a fetched resource
(`$playlist->_rel('tracks')->add([…])`), delegating to the same handle path.

### Custom actions: generated per-action methods under an `actions()` namespace

Resource-scoped actions hang off the id-handle, collection-scoped off the type
accessor, both behind `actions()` — a namespace is needed because an action name can
collide with a generated relation method or a verb.

Signatures derive from the descriptor's action shape:

| descriptor | PHP signature |
|---|---|
| `input: none` | no parameters |
| `input: raw` + `contentType` | `StreamInterface\|string $body` |
| `input: document` + `inputType` | the three write forms for that type (ADR 0003) |
| `output: none` | `void` |
| `output: meta` | `array<string,mixed>` — see prerequisite 6 |
| `output: document` + `outputType`/`outputCardinality` | `<Type>` or `ResourceCollection<Type>` |

**Every generated method carries an accurate `@throws` list.** Each operation
enumerates its error statuses in the spec (400/401/403/404/406/415/422/500), so the
exceptions an operation can raise are known at generation time — and IDEs surface
`@throws` directly.

### Filters: three doors, and the shape must not be unioned with the loose array

The operator is baked in server-side — the client supplies a value per `filter[x]`
param — so this is far simpler than core's filter taxonomy suggests.

| door | catches | role |
|---|---|---|
| `whereTitle('OK')` | filter-name typos (undefined method) | fully safe |
| `filter([…])` | value types including nested; rejects `array<string,mixed>` | typed shape |
| `filterRaw($dyn)` | nothing statically | explicit loose door |

**These must be separate methods.** Verified: a single method typed
`AlbumFilterShape|array<string,mixed>` silently accepts `['title' => 123]` because
the loose branch swallows everything — a union of a shape with a permissive array
gives *zero* checking. Two methods check fully, nested shapes included
(`['releasedAt' => ['min' => 5]]` is caught).

Per-filter methods are generated regardless of schema quality: the value param is
`mixed` where the spec says `"schema": {}` and precise where it doesn't, so
prerequisite 2 landing tightens types on regeneration with no client change. The
immediate win does not wait for the server — a typo'd filter *name* becomes a CI
failure instead of a runtime strict-query-validation error.

**Both array doors need the runtime guard**, not just the loose one. Filters are all
optional, so every key typo sits in ADR 0003's blind spot — `filter(['titel' => …])`
compiles and silently returns an unfiltered collection. Validate keys against the
descriptor's `filterable` tokens and throw `UnknownFilterException` with a
did-you-mean.

Codegen must handle method-name collisions when two filters normalise to the same
name (`artist.name` and `artistName` both → `whereArtistName`), the same detection
already needed for `create`/`update`.

### Countable, sort, and client-generated ids

**Countable.** `withCount` is generated on collection and related query builders
only — no `GET /{type}/{id}` endpoint advertises it, and a single-resource
`withCount` is a 400 under strict validation. Tokens are typed from the descriptor's
enum, and the profile is negotiated automatically when the list is non-empty.

Counts land in two places, and both get typed accessors:

```php
$albums = $client->albums->query()->withCount(['_self_', 'tracks'])->get();
$albums->_page()->total;                // ?int — document meta.page.total
$albums[0]->_rel('tracks')->total();    // ?int — relationship meta.total
$albums[0]->tracks;                     // still throws — not included
```

`_self_` lands at document `meta.page.{total,lastPage}` (omitted in count-free mode,
per `FixedPagePage`), so `_page()` exposes `total` and `lastPage` as `?int`. That is
how a count-free paginator yields a total at all.

**A collision, and why `_rel()` earns its place.** `withCount=tracks` returns a count
*without* including the relation, but accessing an un-included relation throws — so
the count would be unreachable through `$album->tracks`. It resolves with no new
concept: counts read through `_rel()`, already defined as the uniform introspection
door for relations carrying no value.

**Sort** is a variadic of the descriptor's literal tokens, which enumerate both
directions. Codegen emits one alias per type plus one method:

```php
/** @phpstan-type AlbumSortToken 'title'|'-title'|'releasedAt'|'-releasedAt'|'status'|'-status' */

$client->albums->query()->sort('-releasedAt', 'title')->get();
```

Argument order *is* sort precedence — both positional, nothing to translate. Verified
at level 9: a typo'd field, a real-but-unsortable field (`artwork`), wrong direction
syntax (`+releasedAt`) and the comma-joined wire form are all rejected, and a
multi-argument error names the offending position (`Parameter #2 …`).

`sortRaw(list<string>)` is the loose door, validated at runtime against the token
list — needed because a runtime-built `list<string>` cannot satisfy a literal union
(`sort(...$fromRequest)` is rejected). Same two-door split as filters, for the same
reason.

The tokens are strings typed by phpdoc, not a native type, so the runtime guard
remains the real protection if static analysis is bypassed. In exchange the
descriptor's token list *becomes* the type with no generated machinery — no per-type
enum, no value objects, and no extra methods on a builder already carrying `withX()`
and `whereX()`. Rejected: `Sort` VOs over a generated enum (a translation layer over
a signed token the wire uses anyway) and per-field `sortByReleasedAt(desc: true)`
methods (precedence becomes implicit in call order).

**Client-generated ids** follow the descriptor's `clientId` exactly: `forbidden`
generates no id parameter at all, `optional` gives `?string $id = null`, `required`
makes it required. Same rule as `_page()` and relation verbs.

### Projection is its own concern; writes take one, two ways

A write response accepts only `include` and `fields` — `filter`, `sort`, `page`,
`withCount` and `relatedQuery` have no bearing on it, and under the server's strict
query validation a stray `sort` on a POST is a 400, not something silently ignored.
So projection is extracted rather than reusing the query builder:

- `AlbumProjection<TProj>` — `withX()`, `with()`, `fields()`. Nothing else.
- `AlbumQuery<TProj> extends AlbumProjection<TProj>` — adds the read-only concerns
  and the terminal reads.

Verified: `returning()->sort('title')` is `Call to an undefined method
AlbumProjection::sort()`, so the split genuinely excludes them. Also verified that
`@return static<TProj&Marker>` on the base **preserves the subclass** —
`query()->withArtist()` is `AlbumQuery<AlbumBase&AlbumHasArtist>` and the chain
survives in either order — so codegen shares the `withX()` methods rather than
emitting them twice. Cost is one `@phpstan-ignore` per narrowing body, the same
implementation seam accepted elsewhere.

Writes accept a projection as **either a closure or a prebuilt projection object**:

```php
$client->albums->create($input);                              // Album
$client->albums->create($input, fn ($p) => $p->withArtist()); // Album&AlbumHasArtist

$proj = Album::projection()->withArtist()->withTracks();      // reusable
$client->albums->create($input, $proj);
$client->albums->id('1')->update($patch, $proj);
```

All three forms verified at level 9, including that an unprojected relation errors.
The omitted case needs a **conditional return type** — a plain `@return AlbumBase&TProj`
fails with `Unable to resolve the template type TProj` when no projection is passed:

```
@param  (callable(AlbumProjection<AlbumBase>): AlbumProjection<TProj>)|AlbumProjection<TProj>|null $projection
@return ($projection is null ? AlbumBase : AlbumBase&TProj)
```

Rejected: reusing the whole query builder for writes (exposes `sort`/`filter`/`page`
on a POST) and a fluent `returning()->withArtist()->create(…)` (a second entry point
per write, and a dangling projection with no terminal call is the silent no-op we
avoided by keeping `create`/`update` on the accessor).

### Runtime client options, and three smaller parity items

**Per-request header provider on the runtime**, not only in the bridges. A callable
resolved per request, so a refreshed bearer token is picked up without rebuilding the
client:

```php
new Client(baseUrl: $base, headers: fn () => ['Authorization' => 'Bearer ' . $tokens->fresh()]);
```

The bridges then wire this to the container rather than being the only place it
exists. Matches the TS client's `headers?: () => HeadersInit | Promise<HeadersInit>`;
without it a standalone PHP user hand-rolls token refresh.

**Atomic is gated on the capability.** `atomic()` is generated only when the server
advertises the atomic `ext` media type — the same rule as `_page()` and relation
verbs. The TS client threads an `atomic` descriptor and throws when absent; we prefer
not generating it at all.

**Value-level mutation sugar.** A fetched to-many value carries the mutation verbs,
delegating to the handle path — `$playlist->tracks->_add([Track::ref('4')])`, TS's
`playlist.tracks.$add(…)`.

**Spec hash in the generated header**, as TS does, so `--check` compares a hash
rather than re-deriving the whole artifact.

### Remaining shape, following the lineage

- **Transport** is PSR-18 `ClientInterface` + PSR-17 factories + PSR-7, matching
  core's existing PSR alignment, with `php-http/discovery` as an optional
  convenience. The runtime owns content negotiation
  (`application/vnd.api+json`, the atomic `ext` media type). Retries stay out of
  core — the transport's job.
- **Config** is a PHP file returning an array, with one entry per server
  (`input`, `output`, `namespace`, `target`), plus a `--check` drift gate for CI.
- **Validation** takes the TS client's posture: light structural guards only
  (is this a JSON:API document? does `data` carry `type`/`id`?), with full per-field
  validation **opt-in** through a pluggable seam fed by the server's served JSON
  Schemas. `opis/json-schema` is a `suggest`, never a hard dependency — the same
  choice core already made. A missing include stays graceful (leave the relation as
  an identifier) rather than throwing at the boundary.
- **Docs and example** follow the lineage: mkdocs, and an `examples/music-catalog`
  worked reference exercised under PHPUnit, so every documented snippet is a real
  typed call that cannot rot. Directly mirrors core's `examples/music-catalog` and
  the TS repo's `example.test.ts`.
- **Release** via release-please, matching the rest of the suite.

Deferred: any concurrency surface. PSR-18 is strictly synchronous, and JSON:API's
own `include` and atomic operations already cover most batching needs.

## Prerequisites on the server packages

Each confirmed against the music-catalog OpenAPI fixture. Item 1 is a hard
prerequisite for generated typed errors; 2 materially changes what the client can
type and improves the emitted docs; 3 is a correctness trap. Items 4 and 5 are
optional — 4 was demoted when we chose to skip spec detection.

| # | Item | Priority | Evidence in the fixture |
|---|---|---|---|
| 1 | Error code catalogue — open `anyOf`, per-code `status`/`title`/typed `context` | **required** | `Error.code` is bare `type: string` |
| 2 | Filter value schemas | **high** | `filter[title]`, `slug`, `q`, `name`, `rating`, `genres`, `tracks`, `artist.name` all emit `"schema": {}` |
| 3 | Per-field `DateTime` format | **high** | core's field has a configurable `$format` (default ATOM); the spec conveys only `format: date-time` |
| 4 | `info.x-generator: { contract: <int> }` | **high** | a single monotonic integer, bumped whenever the emitted structure changes. Not detection (dropped) — staleness. See below |
| 5 | Response-side `profile=` declaration | optional | zero `profile=` occurrences in any media type; only per-parameter `x-profile`. The client verifies via `jsonapi.profile` in the document instead |
| 7 | Enumerate `JsonApi.profile` / `JsonApi.ext` items | medium | both are declared `array<uri>` with no `enum`, so nothing states which profiles and extensions this server supports |
| 8 | ~~Advertise the JSON Schemas endpoint~~ — **dropped** | — | the served bundle is the OpenAPI components *dereferenced*, nothing more. Verified attribute-by-attribute on `albums`: identical, including `maxLength: 200`, nullable unions, formats and `required`, differing only where OpenAPI uses `$ref: AlbumStatus` and the bundle inlines it. The PHP codegen derives both the types and the validation artifact from `--input` alone |
| 9 | The two artifacts disagree on the type set | medium | the OpenAPI document has 13 `*Resource` schemas, the served bundle has 12 — `users` is absent. Deliberate or not, two artifacts describing one contract that disagree is a drift surface |

**4 — one monotonic contract integer.** Checking the *package* version is the wrong
test: `1.4.2 → 1.4.3` may not change the emitted structure at all while a minor
release might, so semver would make codegen accept or reject for reasons unrelated to
what it reads.

Two failure modes, and only one needs a mechanism:

- **Server older than codegen expects** — a required structure is absent and the
  typed reader already errors clearly. Nothing to add.
- **Server newer than codegen** — the new structure simply is not read, so codegen
  silently emits a client missing capabilities the server offers. Silent
  under-generation is the dangerous case, and the only one this field fixes.

The mechanism is a single field, `info.x-generator.contract`, bumped whenever the
emitted structure changes at all. Codegen declares a supported `[min, max]`:

- `spec.contract > max` → **warning**: newer capabilities exist and were not
  generated; upgrade the codegen.
- `spec.contract < min` → **error**: the document predates what this codegen can
  read.

Server cost is one integer, and the byte-compat baseline already forces the emitted
spec's changes to be noticed, so there is a natural moment to bump it.

Accepted limitation: the warning says something is new, never what. **Rejected: a
`features` token list.** It would name what changed, but at the cost of two
hand-maintained lists that must agree — the server's and the codegen's known-set —
which is the same drift objection that killed the hand-maintained error-code table.

**Dropped from this item** — both have better homes, per the `JsonApi` schema:

- *JSON:API version* is already `JsonApi.version: {const: "1.1"}`. Duplicating it
  would create a second source of truth.
- *Profiles and extensions* belong in `JsonApi.profile.items.enum` and
  `JsonApi.ext.items.enum` (item 7) — spec-native, no extension needed, and exactly
  where both clients already read them at runtime.

## Consistency with json-api-ts

Consistency across the two clients is a goal in itself, so divergences are tracked
rather than discovered later. Items to raise against `json-api-ts`:

| # | Item | Source |
|---|---|---|
| 1 | Descriptor flattens pivot writability — `pivotFields` is name→format, so the `$pivot` write surface cannot exclude read-only fields, despite its CONTEXT.md saying it should. The spec already carries `readOnly`/`required`. | found while designing pivot writes |
| 2 | `$page` never exposes `total`/`lastPage`, though its CONTEXT.md describes `total` as present-but-optional. `withCount=_self_` therefore has to be dug out of raw `$meta.page`, where the server puts `meta.page.{total,lastPage}`. | found while designing Countable |
| 3 | Relationship counts are read as untyped `$rel(name).meta.total`. A typed accessor would match the PHP client's `_rel('tracks')->total()`. | found while designing Countable |
| 4 | Generate per-code error types discriminated on `code` once prerequisite 1 lands, rather than a single `JsonApiError` + matchers. | prerequisite 1 |
| 5 | Filter value typing tightens once prerequisite 2 lands. | prerequisite 2 |
| 6 | Typed action meta results once prerequisite 6 lands. | prerequisite 6 |
| 7 | Drop `--schemas` — the served bundle is the OpenAPI components dereferenced, so the TS codegen can derive its schemas artifact from `--input` the way the PHP one will. Removes an input, a fetch, and a drift surface. | found while scoping prerequisite 8 |

**Deliberate divergences**, recorded so they are not mistaken for drift:

- PHP coerces `date-time`/`number` to `DateTimeImmutable`/`float`; TS passes ISO
  strings through. Justified: JS `Date` is a serialisation hazard, PHP's is not, and
  the PHP server already round-trips these natively.
- PHP reserves a leading `_`; TS reserves `$`. Same spec clause, different
  identifier rules (ADR 0002).
- PHP writes accept three input forms; TS takes flat input only. PHP has no
  structural typing, so the forms have genuinely different guarantees (ADR 0003).
| 6 | Action meta payload schema | medium | a `output: meta` action responds with `MetaDocument`, whose `meta` is the generic `Meta` schema — so the payload is untyped and lands in PHP as `array<string,mixed>`. Letting an action declare its meta shape would make it a typed result DTO |

Explicitly **not** server items, recorded so they are not re-raised:

- **Pivot writability** is already in the spec via `readOnly`/`required`. The gap is
  in the *TS descriptor*, which flattens it away — worth reporting there.
- **Typed `$pivot`** and **JSON Schemas served over HTTP**, both on the TS repo's
  enrichment list, have landed (`meta.pivot.position` is typed with `minimum: 1`;
  the TS example consumes a generated schemas artifact).

**2 — Filter value schemas.** The projector is not at fault: it builds the schema
from `projectConstraints($filter->constraints())` and lets a kind override via
`describeQueryParameter()`. The empty schema means those filters declare **no
constraints**, and constraints are the only source of type information — there is no
fallback to the target field's own type. Two improvements: derive a default value
schema from the target field (`Where` on a `Str` → `{type: string}`,
`WhereIn` → array of that type), and have every filter kind project its structural
shape even with zero constraints (`Range`/`DateRange` → `{min, max}` object,
`WhereIn` → array), as `DateRange` already does. This improves the **emitted docs**
as much as the client: "Filter the collection by `title`" with no type or example is
a weak doc line, and it is weak because the example under-declares constraints.

**Also worth fixing: the music-catalog example under-declares filter constraints.**
It is the fixture the TS codegen was grounded against, so the typed-filter path is
under-tested at both ends of the suite.

**1 — Error codes.** Today `Error.code` is bare `type: string`, so codegen cannot
discover the codes and the client cannot generate typed errors.

A bare enum is not enough — generating `class FilterParamUnrecognized extends
BadRequest` needs the code's status too, and typed context needs the context shape.
The spec already has the right idiom: resource type identity is machine-readable via
`properties.type.const`, which is how the TS codegen derives everything.

**The catalogue must be discoverable without being authoritative.** Applications
throw their own errors with codes the projector never sees, so a closed `oneOf` over
per-code variants is wrong — it would make a server's own error documents fail its
own schema. Instead:

- `ErrorDocument`'s error items keep validating against the **open** generic `Error`
  (`code: {type: string}`), so an unprojected code is always valid;
- per-code variants are published as named `components.schemas` entries
  (`<Code>Error`, each with `code: {const: …}`, `status: {const: …}`, `title`, and a
  typed `context` schema), wired in via `anyOf` that **includes** the open generic
  branch.

Codegen enumerates the named variants; validation stays permissive. If wiring
`anyOf` into `ErrorDocument` risks byte-compat churn, an `x-error-codes` extension
achieves the same and cannot affect validation at all — `x-` is already established
in this projector (`x-enum-varnames`, `x-enum-descriptions`, `x-profile`).

Client-side consequences: an unknown code falls back to the status-based exception
with the raw `code()` still available, so custom server errors degrade rather than
break. And because a document may carry several errors with different codes, the
client specialises only when every error shares one code — otherwise it throws the
status-based exception carrying all of them.

Scope: `json-api` (projector) + both adapters + an OpenAPI byte-compat rebaseline.
The TS repo keeps a parallel "bundle-side enrichment required" list in its
`CONTEXT.md`; this item belongs there too.

## Open questions

The client's own design surfaces are resolved. What remains is sequencing work, not
open design:

- **Server prerequisites 1, 2, 3, 4, 7 and 9** need raising against `json-api` and
  both adapters. Only 1 gates a client capability (generated typed errors); the rest
  improve types, docs, or remove a drift surface.
- **The seven `json-api-ts` consistency items** need raising against that repo.

Deferred by decision, recorded above rather than open: lazy relation fetch, response
caching with conditional requests, and any concurrency surface.
