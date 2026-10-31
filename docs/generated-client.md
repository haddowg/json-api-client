# The generated client

`haddowg/json-api-client-codegen` reads a server's OpenAPI 3.1 document and emits a typed client
over this runtime. This page describes the surface it generates and what the runtime does
underneath.

The client targets documents emitted by `haddowg/json-api` and its Laravel and Symfony adapters.
It reads their profiles, extensions and OpenAPI structure directly, with no detection layer and no
degraded generic mode. [Server prerequisites](server-prerequisites.md) lists what it reads from
the document, and what it still cannot.

Every capability the document does not offer is an absent method rather than a runtime guard.

## Generated output

The output is one file per resource type (`resource-albums.php`), plus `enums.php`, `client.php`
and an `autoload.php` class map the consuming project registers once through composer.json's
`autoload.files`.
Each server gets its own namespace, and the output is committed. Within a file, declarations are
ordered interfaces, traits, enums, abstract bases, then final classes, and emission is sorted and
stable so a regeneration diff contains only the types that changed.

Accessors are methods (`$album->title()`). On a PHP 8.4 target, which defaults to the running PHP
version, each is also a get-only property (`$album->title`) delegating to the method. The
runtime itself requires PHP 8.3.

## Reading resources

Each resource type has a root accessor on the client (`$client->albums`), an id-handle
(`$client->albums->id('1')`), a query builder, and a resource class (`Album`, extending a generated
`AlbumBase`).

### Includes and sparse fieldsets

`withArtist()` includes a relation and narrows the result's type, so `$album->artist()` type-checks
after it and `$album->tracks()` is a static error without `withTracks()`. Narrowing stops at depth
1: deep includes (`tracks.album`) and programmatic include lists go through the untyped
`with(...)`, checked at run time.

Every attribute is always declared on the resource class. A sparse fieldset (`fields()`) changes
only what arrives on the wire, and reading an attribute it excluded throws
`FieldNotSelectedException`. Reading a relation that was not included throws
`RelationNotIncludedException`, at any depth, naming the relation and the fix. `artistRef()` and
`hasArtist()` read the linkage without ever throwing.

### Relationship Queries

A read can also filter and sort an included to-many by the vocabulary the relation's own endpoint
declares, under the Relationship Queries profile. `withX()` takes an optional closure over a
generated `<Type><Relation>RelatedQuery`, which offers the relation's `whereX()`, `filter()`,
`sort()` and their loose doors, checked as a query's are:

```php
$client->albums->query()
    ->withTracks(fn (AlbumTracksRelatedQuery $t) => $t->whereExplicit(false)->sort('-title'))
    ->get();
```

That sends `include=tracks&relatedQuery[tracks][filter][explicit]=false&relatedQuery[tracks][sort]=-title`
and adds the profile to `Accept`, which happens only when a closure actually shapes something. The
closure narrows exactly as a plain `withTracks()` does. The profile has no `page`: the server
renders the relation's first page and links the rest.

The closure exists only where the document permits it: on a to-many whose related read declares a
filter or sort, from a read operation that declares `relatedQuery`. A write does not, so the write
projection's `withX()` takes no closure. A single read that does gets its own
`<Type>ReadProjection`, and `id()->get()` takes either it or a write projection.

### Collections and pagination

A collection read returns a `ResourceCollection<T>` (iterable, countable, with `_meta()` and
`_links()`) for a type that does not paginate, and a `PaginatedCollection<T, TPage>` for one that
does. Only the latter has `_page()`, `_next()`, `_prev()` and `_autoPaging()`, a lazy generator
walking every remaining page, so `_page()` never returns null. `TPage` is the exact page kind
(`PageNumber`, `Offset`, `FixedPage`, `Cursor`), so `$albums->_page()->after` is a static error on
a page-numbered type. A relation's own paginator takes precedence over its related type's.

The codegen reads each page parameter's `x-paginator` stamp for the strategy and its keys, and
without a stamp recognises only the canonical names. An unknown strategy, an unrecognised member
set or an unstamped menu generates the endpoint unpaginated and reports a build-time warning.
Generated parameters keep their role names (`$number`, `$size`) whatever the server calls the key.

A `MultiPaginator` menu gets one picker per strategy, named `pageBy<Kind>()`. A query starts typed
as the default strategy's page and sends no page parameters; a picker sends `page[kind]` with its
own members, replaces any earlier pick, and re-types the query:

```php
$albums = $client->albums->query()->get();                         // PaginatedCollection<AlbumBase, PageNumber>
$albums = $client->albums->query()->pageByCursor(size: 25)->get(); // PaginatedCollection<AlbumBase, Cursor>
```

### Filters, sorts and counts

Filters have three doors: a generated `whereTitle('OK')` per filter, `filter([...])` typed by a
generated array shape, and `filterRaw($dynamic)` for programmatic input. Sorts take the
descriptor's literal tokens as a variadic, in precedence order, with `sortRaw(list<string>)` as the
loose door. Both array doors validate keys against the descriptor at run time and throw
`UnknownFilterException` or `UnknownSortException` with a did-you-mean.

```php
$client->albums->query()->whereTitle('Geogaddi')->sort('-releasedAt', 'title')->get();
```

`withCount([...])` exists on collection and related reads only, with tokens typed from each
endpoint's own enum (so `_self_` appears only where the resource opts in). A `_self_` count lands
on `_page()->total` and `_page()->lastPage`, both `?int`; a relationship count reads through
`$album->_rel('tracks')->total()`, which works whether or not the relation was included.

### The document and reserved accessors

Every accessor the client reserves begins with an underscore, which a JSON:API member name never
can: `_self()`, `_meta()`,
`_links()`, `_link()`, `_raw()`, `_rel()`, `_document()`, `_page()` and the rest.

`_document()` is shared by every resource from one response. Its `jsonapi()` is a typed value
object with `version`, `ext`, `profile` and `meta()`, so a caller can assert which extensions and
profiles the server applied.

Every single-link read (`_self()`, `_link('reissue')`, a relationship's `related()`, a page's
`next()`) accepts both a string link and a `{href, describedby}` link object. `_links()` stays the
raw map. The links a type declares are typed through `_declaredLinks()`, which returns a generated
`<Type>Links` with one nullable accessor per link; a link object has its own class with `href()`,
its static members and a `meta()` read the way attributes are:

```php
$image->_declaredLinks()->download()?->meta()->expiresAt(); // ?\DateTimeImmutable
$image->_declaredLinks()->thumbnail();                      // ?string
```

### Attribute types

Dates and times become `DateTimeImmutable`, `number` becomes `float`, `integer` becomes `int`, and
an enumerated string becomes a generated enum named from `x-enum-varnames`. A `oneOf` with a
discriminator is a tagged union, an `anyOf` one merged shape, and an `allOf` flattened.

## Writing resources

`$client->albums->create($input)` and `->update($input)` always execute and return the resource.
The input is any of three forms:

1. a named-argument DTO, `new AlbumCreate(title: 'Geogaddi')`, which the docs lead with because it
   is the only form PHPStan checks completely;
2. a fluent builder, `Album::create()->title('Geogaddi')->artist($boc)`, for conditional assembly;
3. an array shape, `['title' => 'Geogaddi']`, for programmatic input, validated by a generated
   `from()` that throws `UnknownAttributeException` on an unknown key.

`Album::update()` returns an empty `AlbumUpdate` whose setters each return a new input, so a
discarded setter call is a PHPStan error. Optional members default to the `Missing` sentinel,
keeping an absent member distinct from an explicit `null`.

`when()`, `unless()` and `tap()` work on every builder. On a query builder they never narrow the
projection, so a conditionally included relation is reached through its `…Ref()` or `has…()`
companion.

A write takes an optional projection, as a closure or a reusable projection object, which offers
only `withX()`, `with()` and `fields()`:

```php
$client->albums->create($input, fn ($p) => $p->withArtist()); // Album&AlbumHasArtist
$proj = Album::projection()->withArtist()->withTracks();
$client->albums->id('1')->update($patch, $proj);
```

## Atomic operations

When the server advertises the Atomic Operations extension, the client has an `atomic()` method.
Its callback receives a recorder, `Tx`, whose `create()`, `update()` and `delete()` each record one
operation in call order. Nothing is sent until the callback returns; then the batch goes as one
request, and either every operation commits or none does.

```php
[$artist, $album] = $client->atomic(function (Tx $tx) {
    $a = $tx->create(Artist::create()->name('Boards of Canada'));
    $b = $tx->create(Album::create()->title('Geogaddi')->artist($a));

    return [$a, $b];
});

$album->resource()->title(); // 'Geogaddi'
$artist->id();               // the id the server assigned
```

`atomic()` returns whatever the callback returns, typed as it was returned, so the handles come
back exactly where the callback put them. `create()` takes a create input or builder and returns a
`Created<T>`; `update()` takes a target and an update input and returns an `Updated<T>`; `delete()`
takes a target and returns nothing. Each handle's `resource()` reads its operation's result once the
batch has committed and throws before then. An update the server applied exactly as sent may have
no result, so `Updated::resource()` is nullable.

A create carries a local id, `atomic-<index>`, unless its input has a client-generated id. A
`Created` handle stands in for the new resource anywhere a later operation in the batch needs it:
as an update's or a delete's target, and in a relation setter, whose type accepts a `Created` of the
related type only on a server with the extension. An update or delete can also target a resource
in hand or an `Identifier`.

When the server rejects the batch, nothing was committed and `atomic()` throws the status or code
class for the response. Each error's `opIndex` names the operation it blames, in call order, and its
`path` the input member within it, so `byPath()` groups a validation failure the way it would for
a standalone write.

## Relationships

Each relation has a method on the id-handle exposing only the verbs the document permits: a to-one
has `set()` and no `add()`. The related read and the linkage read are generated independently,
each only when its endpoint exists.

```php
$pl = $client->playlists->id($id);
$pl->tracks()->add([Track::ref('4')]);
$pl->owner()->set(User::ref('7'));
$pl->orderedTracks()->replace([new PlaylistOrderedTracksEdge(Track::ref('4'), position: 1)]);
```

A fetched resource reaches the same handle through `_handle()`, so writing to something just read
needs no id bookkeeping, and offers exactly the verbs its relations permit:

```php
$playlist = $client->playlists->id($id)->get();
$playlist->_handle()->tracks()->add([Track::ref('4')]);
```

A pivot write goes through a generated edge class carrying only the fields a write may set, with
required ones enforced. A pivot read goes through `<rel>Pivots()` on the parent resource, one
`<Type><Rel>Pivot` per linkage member in linkage order, each with `_identifier()` naming the member
it belongs to. A field the server always renders reads strictly and throws `MalformedDocument` when
missing; any other reads as nullable. The pivot lives on the parent because a related resource is
the same class however it was reached.

## Custom actions

Actions hang off `actions()`, on the id-handle for a resource-scoped action and on the type
accessor for a collection-scoped one, so an action name never collides with a relation or verb.

| action declares | generated signature |
|---|---|
| no input | no parameters |
| a raw body | `StreamInterface\|string $body` |
| a JSON:API document | the three write forms for that type |
| a form body | a field array, narrowed to an array shape when the schema lists `properties` |
| no output | `void` |
| a meta result | `array<string, mixed>` |
| a JSON:API document | `<Type>` or `ResourceCollection<Type>` |
| a URL answer | `ActionLocation`, or its union with the document result |

Raw response media types are chosen by a trailing `$accept` and several raw request media types by
a leading `$contentType`, each typed by a PHPStan conditional. A binary body is
a `StreamInterface`, text a `string`, a JSON object with members a generated value class, and any
other JSON `mixed`. `$accept` defaults to JSON:API when the action also answers with it.

A form body is a field array the runtime encodes through `Request::withMultipartBody()` or
`withFormBody()`, choosing the multipart boundary itself. A `FilePart` carries a file's bytes,
filename and media type, a boolean is sent as `1` or `0`, and a list is sent as one `name[]` part
per item.

An action that answers with a URL returns an `ActionLocation` (`url`, `type` and a nullable
`expiresAt`), read from the document's `links.location`. The runtime never follows the URL.

An action's `summary` and `description` become the method's docblock, and every generated method
carries a `@throws` list of the error classes its operation can raise.

## Profiles and extensions

Negotiation is automatic. The client adds a `profile` parameter to `Accept` exactly when a request
uses a parameter whose `x-profile` names that profile, and the atomic extension's media type is
negotiated on both `Content-Type` and `Accept`.

## Errors

A JSON:API error response throws a `JsonApiErrorResponse` subclass: a status class (`BadRequest`,
`NotFound`, `ValidationFailed`, `Conflict`, …) or, when every error in the document shares one
catalogued code whose status matches the response, that code's class, which extends its status
class. Core's codes
ship in the runtime and the codegen emits only the ones it lacks. An unknown code falls back to the
status class with the raw code still readable.

Every error response exposes `statusCode()`, `errors()`, `withCode()` and status matchers such as
`is4xx()` and `isUnprocessable()`. A code class adds `pointers()` or `parameters()` when its
catalogue entry requires `source.pointer` or `source.parameter`, and its docblock carries the
catalogue's description. `byPath()` maps each error's `source.pointer` back to the caller's input
path: `/data/attributes/releaseInfo/label` becomes `releaseInfo.label` and a pivot member becomes
`orderedTracks[0]._pivot.position`.

## The runtime

- **Transport** is PSR-18 with PSR-17 factories and PSR-7 messages, discovered through
  `php-http/discovery` when none are given. The runtime owns content negotiation. Retries are the
  transport's job.
- **Headers** come from an optional callable resolved per request, so a refreshed bearer token is
  picked up without rebuilding the client:

  ```php
  new ClientOptions(baseUrl: $base, headers: fn () => ['Authorization' => 'Bearer ' . $tokens->fresh()]);
  ```

- **Requests stay on the base URL's origin.** The transport follows an absolute URL the server
  handed back, such as a `next` link, only when its scheme, host and port match the base URL's, and
  otherwise throws `CrossOriginRequestException` before resolving any headers. A server that pages
  onto another origin is better served by a second client with its own base URL.
- **Framework bridges** for Laravel and Symfony register each generated client as a service over
  the framework's HTTP client, and add `json-api-client:generate` and `json-api-client:check`. See
  [Laravel and Symfony](frameworks.md).
- **Validation** is structural by default: is this a JSON:API document, and does `data` carry
  `type` and `id`. Opting in checks every successful response against the schema the server's own
  OpenAPI document gives its operation, through `opis/json-schema`, and throws `SchemaViolation`
  listing each failing pointer:

  ```php
  $options = $options->withValidator(OpenApiValidator::fromFile(__DIR__ . '/openapi.json'));
  ```

  A request carrying a sparse fieldset is checked without the read schema's `required` lists,
  since the server legitimately leaves members out. A response the document does not describe is
  not checked.

## Not provided

- **Lazy-fetching an un-included relation.** A hidden HTTP call per access inside a loop is the
  classic way to melt an API.
- **Response caching and conditional requests.** Invalidating a compound document spans its
  `included` resources, and getting that wrong serves stale data silently.
- **Concurrency.** PSR-18 is synchronous, and `include` and atomic operations already cover most
  batching.
