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

**Codegen requirement — `??` is forbidden when reading write input.**
`$input['x'] ?? Missing::Value` folds an explicit `null` into "absent", so a `PATCH`
setting a member to `null` sends nothing. That is precisely the bug the sentinel
exists to prevent, in the code that exists to serve it. Every member must be read with
`array_key_exists`. PHPStan cannot see this; only a test catches it.

**Codegen requirement:** `@phpstan-type` aliases are class-local. Emit them, with the
descriptor constants the runtime guards read, on the concrete resource class (`Album`)
and pull them in with `@phpstan-import-type … from Album` wherever they are referenced —
without the import the alias silently degrades to bare `array` and every array-branch
check is lost. A separate holder class earned nothing but a file, so there is none.

### Write builders are static factories on the resource class

`Album::create()` returns a create builder; `Album::create([…])` returns the create
DTO. The arity overload is expressible in phpdoc
(`@return ($input is null ? AlbumCreateBuilder : AlbumCreate)`).

`update` has no builder. Every update member is optional, so the builder would hold
exactly the DTO's state: `Album::update()` returns an empty `AlbumUpdate` whose fluent
setters each return a new input, and `Album::update([…])` returns one built from the
array. Because the setters return rather than mutate, they are `@phpstan-pure`, so a
discarded call (`$update->title('x');` as a statement) is `method.resultUnused` instead
of a silent no-op. **Codegen requirement:** mark the update DTO's constructor and every
fluent setter `@phpstan-pure`; without the constructor annotation PHPStan reports
`possiblyImpure.new` in each setter. The protection is static only: code that skips
PHPStan loses a discarded setter call, where a mutable builder would have kept it.

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

- **Immutable write inputs** (the update DTO) — `ImmutableConditionable`,
  `callable(static): static`. Returning `null` is not accepted, because a callback that
  calls a setter and returns nothing would drop the change; requiring `static` makes it
  an `argument.type` error.
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

### A paginator menu is a typed picker

From generator contract 3 the server stamps each `page` schema with `x-paginator`, naming the
strategy and the key that fills each role. The codegen trusts that stamp outright. It infers
from names only without one, and only from the canonical sets: `number`+`size`,
`offset`+`limit`, a cursor bound with an optional `size`, and a lone `number`, which is fixed.
Anything else, a strategy it does not know, or a menu with no stamp is a build-time warning and
an unpaginated collection, never an error. Generated method parameters keep their role names,
`$number` and `$size`, and map to the server's real keys. A renamed `page[p]` changes the wire,
not the call.

A `MultiPaginator` menu offers several strategies under a `page[kind]` discriminator, with one
default the server uses when the request names none. Its query builder carries a second
template, `TPage`, and offers one picker per arm, named after the arm's kind:

```php
$albums = $client->albums->query()->get();                       // PaginatedCollection<AlbumBase, PageNumber>
$albums = $client->albums->query()->pageByCursor(size: 25)->get(); // PaginatedCollection<AlbumBase, Cursor>
```

A query starts typed as the default arm's page and sends no `page` parameters at all. A picker
sends `page[kind]=<kind>` explicitly with that arm's members, *replaces* whatever an earlier
picker set, and returns the query re-typed to that arm's page. The query reads its page through
a reader closure chosen with the arm, so `get()` returns `PaginatedCollection<TProj, TPage>`
with no assertion of its own. Replacing goes through the runtime's `repaging()`, because merging
would send `page[after]` beside `page[kind]=page`.

The picker is named `pageBy<Kind>()` rather than after the bare kind because a kind is
server-chosen and `page`, `first` or `sort` would collide with methods the query already has.
The menu query cannot use the shared `withX()` trait: `static<TProj&Marker>` names one template
of a two-template class, which PHPStan reports as `generics.lessTypes`, and the page choice
would be lost across the call. Its `withX()` methods are written inline as `static<TProj&Marker,
TPage>`, which keeps the choice in either order along the chain. See ADR 0006.

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
$playlist->orderedTracksPivots()[0]->_identifier();     // the member a pivot belongs to
$album->_document()->jsonapi()->version;   // and ->ext, ->profile, ->meta()
```

Every read of one link (`_self()`, `_link('reissue')`, a relationship's `related()`, a page's
`next()`) goes through `Wire::href()`, which takes a string or a link object. From generator
contract 7 a server renders the links it builds as `{href, describedby}` objects, and may opt
back out to strings, so both have to work. `_links()` stays the raw map. Links are not typed per
context, matching the TS client; a typed accessor for named action links is open for both.

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
2. **Code-specific subclasses**, one per catalogued code, each extending its status
   class (`FilterValueInvalidError extends BadRequest`) with a `CODE` constant. Core's
   catalogue is the same for every API built on `haddowg/json-api`, so its codes **ship
   in the runtime** (`src/Exceptions/Codes`) instead of being generated per API. They
   are still generated, never hand-written: `bin/generate-error-codes.php` emits them
   from an OpenAPI document, and a parity test regenerates them from the committed
   catalogue and fails if `src/` differs. A per-API run then generates classes only for
   codes the installed runtime lacks, which covers application-contributed codes and a
   server newer than the client. See ADR 0005.

   **Dispatch:** `JsonApiErrorResponse::for()` uses a code's class only when every error
   in the document shares that one catalogued code *and* the code's status matches the
   response. Mixed codes, an uncatalogued code or a mismatched status all fall back to
   the status class carrying every error, so nothing ever throws for an unknown code.

   **What a code class carries** is exactly what the catalogue states: the code and
   status, plus `pointers()` on the 17 codes whose variant requires `source.pointer` and
   `parameters()` on the 14 that require `source.parameter`. The 1.1.0 catalogue
   declares **no context schema** — `Error.meta` stays a bare object — so typed context
   properties (`$e->filterParam`) have no source in the contract and are not generated.
   They need core to publish a per-code `meta` schema first.

   Since core 1.3.0 each `<Code>Error` component also carries a schema `description`, what
   the code means and what to do about it, and sometimes an example `detail`. Both go into
   the class docblock, so an IDE shows them on hover; neither changes the class's API.

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

### Codegen output — one file per resource type, a generated class map, committed

Generated code is grouped **one file per resource type** (`resource-albums.php` holds
every class the `albums` type generates), plus `enums.php`, `client.php`, and
`autoload.php`, a generated class-to-file map the user registers once through
composer.json's `autoload.files`. One namespace per server, **committed** — reviewable,
diffable and versioned, the same principle as the TS client's committed output. See
ADR 0004.

Measured against the 1.1.0 music-catalog document, the default server's surface is about
170 classes. One class per file would be about 170 files; grouped, it is about 19.

- **File names carry a hyphen** (`resource-albums.php`), which no class name can. A bare
  `albums.php` is the same file as the `Albums` accessor's on a case-insensitive
  filesystem, and a PSR-4 lookup for `Albums` would load it.
- **Declarations are ordered** interfaces, traits, enums, abstract bases, then final
  classes, so every parent is declared before its child within a file.
- **A map, not `classmap` autoloading.** Composer's `classmap` would work too (both were
  verified at level 9), but every regeneration would then need a `dump-autoload`, and a
  forgotten one fails only for newly added classes. The generated map is rewritten with
  the code, so it cannot go stale.

Emission is sorted and stable so a regen diff contains only genuinely-changed types.

Rejected: one class per file under PSR-4, the original choice. It costs ~170 files for no
guarantee the grouped form lacks, and a regen touching one type then spreads across a
dozen files. Also rejected: a single ~5,000-line file. That file is one file in TS
because a TS module *is* one file; in PHP it degrades review, merge conflicts and
PHPStan cache granularity all at once. Per-type files keep each of those scoped to a
type. And rejected: generating at install time into a gitignored directory, which trades
committed code for a committed spec and lets someone else's API break your production
build.

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
    new PlaylistOrderedTracksEdge(Track::ref('4'), position: 1, weight: 5),
]);
```

Pivot writes go through a generated edge builder carrying **only writable** fields,
with required ones enforced. Not an array shape: pivot payloads are mostly-optional,
which is exactly where an array shape silently swallows a typo (ADR 0003) — and
`positon` compiling while quietly dropping the ordering is a bad failure.

**Pivot writability needs no server change.** The spec expresses it in standard
OpenAPI. From generator contract 8 a pivot relation has two schemas (core ADR 0160):
`<Type><Rel>Pivot` is what a response renders, and `<Type><Rel>WritePivot` is what a
write may send, reached through `<Type><Rel>WriteRelationship` in create and update
bodies and `<Type><Rel>RelationshipRequest` on the relationship endpoint. The edge
builder is generated from the write schema, so a field no write can set (`addedAt`)
is simply not there. An older document shares one pivot schema between both
directions and flags such a field `"readOnly": true`, which the codegen drops from
the edge the same way. Reading `readOnly` properly also gets read-only *attributes*
right for free: they appear on the read DTO and not in `<Type>Create`/`<Type>Update`.

Pivot reads are generated from the response schema. Each pivot relation gets a
`<Type><Rel>Pivot` class extending the runtime's `Pivot`, with one accessor per field,
and the resource base gets `<rel>Pivots()`, the pivot of each linkage member in
linkage order:

```php
foreach ($playlist->orderedTracksPivots() as $pivot) {
    $pivot->position();      // positive-int: always rendered, never null
    $pivot->weight();        // ?int: always rendered, may be null
    $pivot->addedAt();       // \DateTimeImmutable
    $pivot->_identifier();   // the linkage member it belongs to
}
```

A field the server always renders is in the response schema's `required` and reads
through a strict reader, which throws `MalformedDocument` when it is missing. Any
other field reads as nullable, because a pivot takes no sparse fieldset and an absent
field is simply absent. The nth pivot belongs to the nth member of `<rel>Ref()`, and
of `<rel>()` when the relation was included, since both follow linkage order. The
accessor never needs the include, because the pivot rides on the linkage.

The pivot lives on the parent, not on the related resource. A `Track` hydrated from
`orderedTracks` is the same class as one hydrated from `tracks` or fetched on its
own, so a typed `$track->_pivot()` could only be `array<string, mixed>`.

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

**A raw response is picked by `$accept`, typed by a conditional return (ADR 0007).** From
contract 10 an action's success response can list media types other than JSON:API under
its `content`, each with its own schema. The generated method gains a trailing
`string $accept` parameter, PHPDoc-typed to the literal union of the declared media types,
and its `@return` is a PHPStan conditional on that parameter: `StreamInterface` for a
`format: binary` body, `string` for a text body, a generated value class for a JSON body
whose schema is an object with members, and `mixed` for any other JSON body. `$accept`
defaults to JSON:API when the action declares a JSON:API response too, so an existing call
is unchanged; an action that answers only in raw media types defaults to the first one, and
one with a single raw media type takes no `$accept` at all. The runtime sends the chosen
type as `Accept` through `Request::withAccept()`, and reads the body with `Fetcher::text()`,
`stream()`, `json()` or `jsonObject()`. An error still arrives as a JSON:API document and throws as usual.

**Several request media types are picked by `$contentType`.** An action whose request body
lists more than one non-JSON:API media type, or a wildcard such as `image/*`, takes
`$contentType` as its first parameter. A method template bound to the declared literals
types it, plus `non-empty-string` when a wildcard is declared, and the body's `@param` is a
conditional on that template: `StreamInterface|string` for a binary type, `string` for text,
the schema's array shape for JSON, which the method encodes. The generated method refuses a
`$contentType` the action does not declare. An action with one concrete raw media type keeps
the single-body signature above. A JSON:API body offered beside raw ones is generated as the
document input alone, with a warning.

**Summary and description become the method's docblock.** An action's `summary` replaces
the humanised action name at the head of the docblock and its `description` follows as a
paragraph, unless they are the projector's own defaults ("Invoke the `x` action"), which
say nothing the method name does not.

### Filters: three doors, and the shape must not be unioned with the loose array

The operator is baked in server-side — the client supplies a value per `filter[x]`
param — so this is far simpler than core's filter taxonomy suggests.

| door | catches | role |
|---|---|---|
| `whereTitle('OK')` | filter-name typos (undefined method) | fully safe |
| `filter([…])` | value types including nested; rejects `array<string,mixed>` | typed shape |
| `filterRaw($dyn)` | nothing statically | explicit loose door |

**The loose doors exist only where the contract permits them.** The runtime's `Query` and
`Projection` keep `filterRaw()`, `sortRaw()`, `pageRaw()`, `with()` and `fields()` protected,
and a generated builder re-declares each one public only where its endpoint declares the
matching parameter. A type whose collection takes no filter has no `filterRaw()` at all, the
same rule as every other absent capability.

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

**Both array doors need the runtime guard**, and it must reach *nested* members.
An earlier claim that the typed door catches nested shapes was too broad: it catches a
wrong nested *value type* (`['releasedAt' => ['min' => 5]]`), but an unknown nested
*key* is invisible — `filter(['rating' => ['minimum' => 4.0]])` compiles and filters
nothing. Filters are all optional, so every key typo sits in ADR 0003's blind spot — `filter(['titel' => …])`
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
$tracks = $client->tracks->id($id)->playlists()->withCount(['_self_'])->get();
$albums->_page()->total;                // ?int — document meta.page.total
$albums[0]->_rel('tracks')->total();    // ?int — relationship meta.total
$albums[0]->tracks;                     // still throws — not included
```

`_self_` lands at document `meta.page.{total,lastPage}` (omitted in count-free mode,
per `FixedPagePage`), so `_page()` exposes `total` and `lastPage` as `?int`. That is
how a count-free paginator yields a total at all.

**`_self_` is a per-resource server opt-in** (`CountableSelfInterface`), so it appears
in some `withCount` enums and not others — present on `/albums/{id}/tracks` and
`/tracks/{id}/playlists`, absent from `/albums`, `/artists/{id}/albums`, `/playlists`
and `/tracks`. Not an omission to work around: the token union is generated from each
endpoint's own enum, so the rule handles it exactly right.

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
survives in either order. **But only for `final` classes** — corrected twice, so the
history matters: a spike suggested one `@phpstan-ignore` per narrowing body, a first
correction removed it, and building the real artefact showed the clean result holds
only when the class is `final`. The moment `AlbumQuery extends AlbumProjection`, level
9 rejects it: `should return static(AlbumProjection<AlbumHasArtist&TProj>) but returns
static(AlbumProjection<TProj>)`.

So the two builders do **not** share an inheritance relationship. `withX()` lives in a
generic trait (`@template TProj` + `@phpstan-require-extends`), used by two `final`
classes. Analysed once per using class, passes for both, no duplicated bodies, no
ignore. Nothing depended on the `extends` except method sharing.

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

**Requests stay on the base URL's origin.** The provider's headers go on every request, so
the transport only sends an absolute URL, such as a `next` link the server handed back, when
its scheme, host and port match the base URL's. Anything else, including a protocol-relative
`//host` and the backslash forms some parsers read as an authority, throws
`CrossOriginRequestException` before the headers are resolved. There is no opt-in to follow
such a link without the headers: a server that paginates onto another origin is better served
by a second client with its own base URL. The TS client should hold the same line.

**Atomic is gated on the capability.** `atomic()` is generated only when the server
advertises the atomic `ext` media type — the same rule as `_page()` and relation
verbs. The TS client threads an `atomic` descriptor and throws when absent; we prefer
not generating it at all.

**Value-level mutation sugar.** A fetched to-many value carries the mutation verbs,
delegating to the handle path — `$playlist->tracks->_add([Track::ref('4')])`, TS's
`playlist.tracks.$add(…)`.

**Spec hash in the generated header**, as TS does, so `--check` compares a hash
rather than re-deriving the whole artifact.

### One runtime erasure point per generated read class is unavoidable

The runtime always constructs the concrete `Album`; `TProj` is a template no bound can
satisfy, and typing the result `Album&TProj` collapses to `Album` and undoes every
narrowing. `/** @var TProj */` is itself rejected (`varTag.nativeType`: `TProj of
object` is not a subtype of native `Album`).

So there is exactly one erasure point, and it belongs in the runtime rather than in
each emitter: `ResourceContext::hydrate(): object`. Stated here because six emitters
would otherwise each invent a different workaround for the same wall.

A paginator menu's query adds a second, for the same reason: re-typing `TPage` on a clone is
an assertion, and `/** @var self<TProj, Cursor> */` over `static` is rejected the same way. The
runtime's `Query::repaging()` returns the base `Query<TProj>`, which a generated `choose()`
narrows once for every picker. Single-strategy queries have no second template and no second
erasure.

### Composed attribute schemas follow the keyword's own semantics

- **`oneOf` + `discriminator`** — a tagged union of generated branch classes, narrowed
  with `instanceof`/`match` (`ReleasesAttributes.format`: vinyl/cd/digital/null keyed
  on `medium`).
- **`anyOf`** — ONE merged shape with every branch member optional. `anyOf` is not
  exclusive: `{worldwide: true, regions: [...]}` satisfies two branches of
  `ReleasesAttributes.availability` and is legal, so a union of branch classes would
  be a type that lies about a document the server can send.
- **`allOf`** — flattened and merged.
- A `{"type": "null"}` branch becomes nullability rather than a branch class, so
  `anyOf[null, allOf[...]]` (`dimensions`) is just a nullable merged object.

A bare union with no declared type keeps `format: unknown` — honest, since the
branches are the whole description.

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
| 3 | ~~Per-field `DateTime` format~~ — **DONE** (core #160). Projector now probes the configured format and emits `format: date-time` only when reference instants round-trip. Found a second bug: `Time`'s default `H:i:s` was never a valid `format: time` | — | core's field has a configurable `$format` (default ATOM); the spec conveys only `format: date-time` |
| 4 | ~~`info.x-generator: { contract: <int> }`~~ — **DONE** (core #159) | — | a single monotonic integer, bumped whenever the emitted structure changes. Not detection (dropped) — staleness. See below |
| 5 | Response-side `profile=` declaration | optional | zero `profile=` occurrences in any media type; only per-parameter `x-profile`. The client verifies via `jsonapi.profile` in the document instead |
| 7 | ~~Enumerate `JsonApi.profile` / `JsonApi.ext` items~~ — **ALREADY DONE**; only looked outstanding because the original reading was of a stale fixture | — | both are declared `array<uri>` with no `enum`, so nothing states which profiles and extensions this server supports |
| 8 | ~~Advertise the JSON Schemas endpoint~~ — **dropped** | — | the served bundle is the OpenAPI components *dereferenced*, nothing more. Verified attribute-by-attribute on `albums`: identical, including `maxLength: 200`, nullable unions, formats and `required`, differing only where OpenAPI uses `$ref: AlbumStatus` and the bundle inlines it. The PHP codegen derives both the types and the validation artifact from `--input` alone |
| 10 | ~~Write operations declare no `include`/`fields`~~ — **DONE** (core #161). Gate is "does the declared success response carry a resource document of this type?", not "is it a write?" — relationship mutations and `202` job responses correctly excluded | — | 0 of 45 POST/PATCH operations declare either, though the server honours them: `StrictQueryParameterValidator` gates on family name, is request-agnostic, and keeps `include`/`fields` permanently reserved. So the runtime accepts them and only the projector fails to declare them. The write-projection surface is **not generated until this lands** — emitting a parameter the document never declares, justified by reading the server's source, is exactly the coupling the no-detection-layer decision avoided |
| 9 | ~~The two artifacts disagree on the type set~~ — **DONE** (core #158, `ProjectedTypes`). Bigger than first seen: default missing `users`, admin missing 4. Adapter adoption still pending, and both adapters must land it in the SAME release or byte-compat turns red | — | the OpenAPI document has 13 `*Resource` schemas, the served bundle has 12 — `users` is absent. Deliberate or not, two artifacts describing one contract that disagree is a drift surface |

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
| 4 | **Typed error handling — now unblocked.** Prerequisite 1 landed (core #162/#168/#170), so the document carries 53 named `<Code>Error` variants with `code`/`status` consts, a typed `source` narrowing, and per-status narrowed `ErrorDocument<NNN>` schemas behind nine shared `components.responses`. The TS client should generate per-code error types discriminated on `code` instead of one `JsonApiError` + matchers. Note the catalogue is **open** — an unlisted code must fall back, never throw. | prerequisite 1, now landed |
| 10 | **Two latent readers of the same class as the pagination bug.** `okResponseRef` reads `responses['200']` literally, so moving 2xx responses into `components.responses` would silently strip every type's collection path, paginator and actions. Every query-parameter reader matches `param.name` directly, so moving parameters into `components.parameters` would silently vanish pagination, `include`, `sort`, `filter[…]` and `withCount`. Neither is triggered today — error responses were safe only because non-2xx entries are skipped before `.content` is touched — but both are traps for any future projector tidy-up. | found while moving error responses to `components.responses` (core #170) |
| 5 | Filter value typing tightens once prerequisite 2 lands. | prerequisite 2 |
| 6 | Typed action meta results once prerequisite 6 lands. | prerequisite 6 |
| 9 | **CRITICAL — pagination is undetectable on a v1 document.** `json-api` v1.0.0 (`845be93`, released 2026-07-09, four days before the TS client shipped) projects the whole `page[…]` family as ONE parameter named `page` with `style: deepObject`. The TS codegen looks for `page[number]`/`page[size]`, finds neither, and returns `none` — and its fallback warning cannot fire, because it tests `name.startsWith('page[')` and the name is exactly `page`. Result: every paginated collection generates as unpaginated, silently, with no `$page`/`$next()`/`$prev()`. Its tests pass only because the committed fixture predates the change. | found by verifying the wire form against the server rather than the fixture |
| 8 | **`PaginatorKind` has no `fixed` case.** `FixedPagePaginator` advertises only `page[number]` (size is fixed server-side), so it matches neither `page` (which needs `page[size]`) nor any other kind. The TS detector warns and falls back to `none`, silently losing `$page`/`$next()` on an endpoint that really is paginated. | found while building the PHP Page kinds |
| 11 | **Pagination from `x-paginator` (contract 3).** Both codegens now follow the same rules: the stamp decides strategy and keys; without it, canonical names only, with a lone `number` as fixed; anything unrecognised or an unknown strategy warns and goes unpaginated; a menu is a picker by `kind` whose unchosen result is the default arm's page. The TS codegen was changed to these rules alongside the PHP one; picker naming is per-language (`pageBy<Kind>()` here). Supersedes items 8 and 9 against a contract-3 server. | core #175 |
| 12 | **Raw action bodies (contract 10).** Both clients name the response choice `accept` and the request choice `contentType`. PHP passes them as parameters, `$accept` last and `$contentType` first, because a PHP signature has no options bag and a conditional type needs a real parameter to key on. PHP answers a binary body as a PSR-7 `StreamInterface` where TS answers a `Blob` or `ArrayBuffer`, and a JSON body with an object schema as a generated value class where TS uses the schema's type. | core 1.11.0 |
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
