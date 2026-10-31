# json-api-client

The PHP client for JSON:API 1.1 services built on `haddowg/json-api`: a runtime, plus the typed
code `haddowg/json-api-client-codegen` generates from a server's OpenAPI 3.1 document.

## Language

### Documents

**Document**:
The top-level JSON object of a response, holding resource objects, relationship objects and
resource identifier objects, in JSON:API's own vocabulary.
_Avoid_: Envelope, payload

**Generator contract**:
The integer `info.x-generator.contract` a server stamps on its OpenAPI document, which the codegen
compares against the range it supports.
_Avoid_: Spec version, schema version

**Descriptor**:
The codegen's model of one API read from its document: each type's attributes, relations,
endpoints, paginator, tokens and actions.
_Avoid_: Resource map, spec model

### Generated surface

**Type accessor**:
The client's entry point for one resource type (`$client->albums`), which lists, queries and
creates.

**Id-handle**:
The handle for one resource by id (`$client->albums->id('1')`), carrying its update, delete,
relation and action methods without fetching it.
_Avoid_: Resource reference, proxy

**Resource class**:
The generated class for one resource type (`Album`, over a generated `AlbumBase`), declaring every
attribute and relation the document gives it.
_Avoid_: Resource DTO, model, entity

**Reserved accessor**:
A client-owned accessor on a resource or collection, always named with a leading underscore
(`_meta()`, `_page()`).
_Avoid_: Envelope accessor

**Relation marker**:
A generated interface per relation (`AlbumHasArtist`) that an include adds to the projection.

**Projection**:
The type a read or write returns, narrowed by the relation markers its includes add
(`AlbumBase&AlbumHasArtist`).

**Query builder**:
The generated read builder for a type (`AlbumQuery`), carrying includes, fieldsets, filters,
sorts, counts and pagination.

**Projection builder**:
The generated builder a write takes for its response (`AlbumProjection`), carrying includes and
fieldsets only.

**Selected / included**:
An attribute is selected when no sparse fieldset excluded it; a relation is included when the
request's `include` named it.

**Door**:
One of the input forms a method family offers: a typed door checked by PHPStan (`filter()`), and a
loose door taking programmatic input checked only at run time (`filterRaw()`).
_Avoid_: Raw API, escape hatch

### Writes

**Write input**:
The canonical named-argument class a create or update serialises from (`AlbumCreate`,
`AlbumUpdate`).
_Avoid_: Payload, request DTO

**Create builder**:
The fluent builder that builds a create input (`AlbumCreateBuilder`), reached through
`Album::create()`.

**Missing**:
The sentinel that marks an optional write member as not given, distinct from an explicit `null`.
_Avoid_: Undefined, unset

### Atomic batches

**Batch**:
The writes recorded inside one `$client->atomic()` callback and sent as one all-or-nothing
`atomic:operations` request.
_Avoid_: Transaction, bulk write

**Recorder**:
The `Tx` a batch callback receives, which records each create, update and delete in call order.

**Operation handle**:
What the recorder returns for a create or update (`Created<AlbumBase>`, `Updated<AlbumBase>`),
standing in for the resource until the batch commits and reading its result afterwards.
_Avoid_: Promise, future

**Local id**:
The `lid` (`atomic-<index>`) a create carries so a later operation in the same batch can reference
the resource before the server has given it an id.

### Relationships

**Relation handle**:
The generated object for one relation of an id-handle (`AlbumArtistRelation`), exposing only the
verbs that relation permits.

**Pivot**:
The `meta.pivot` a pivot relation renders on each linkage member, read through the parent's
`<rel>Pivots()`.
_Avoid_: Join data, edge meta

**Edge**:
A generated pivot write (`PlaylistOrderedTracksEdge`): one related identifier with the pivot
fields a write may set.

### Pagination

**Page kind**:
The pagination strategy a collection uses, one of page number, offset, fixed page and cursor, each
with its own page class.
_Avoid_: Paginator type

**Paginator menu**:
An endpoint offering several page kinds under a `page[kind]` discriminator, with one default.
_Avoid_: Multi-paginator

**Arm**:
One page kind offered by a paginator menu.

**Picker**:
A generated `pageBy<Kind>()` method that selects an arm and re-types the query to its page.

### Actions and links

**Action location**:
The URL, media type and optional expiry an action answers with instead of a document.

**File part**:
A file in a form body, carrying its bytes, filename and media type.

**Declared link**:
A link a resource type declares by name in its schema, typed through `_declaredLinks()`.

### Frameworks

**Bridge**:
The Laravel or Symfony integration in this package, which registers configured clients as
services and adds the generate and check commands.
_Avoid_: Adapter, integration package

**Drift check**:
`json-api-client:check`, which compares the sha256 a generated client records with the document
its server serves now.

**Fake transport**:
The queue-answering, request-recording PSR-18 client tests swap in (`FakeTransport`).
_Avoid_: Mock client

**Response validation**:
The opt-in check of each successful response against the schema the server's OpenAPI document
gives its operation, which throws `SchemaViolation`.
_Avoid_: Schema checking, strict mode

### Errors

**Error catalogue**:
The set of error codes a server documents, each with its status, title and required source.

**Status class**:
The exception thrown for a JSON:API error response by its HTTP status (`NotFound`,
`ValidationFailed`).

**Code class**:
The exception for one catalogued error code (`FilterValueInvalidError`), extending its status
class.
_Avoid_: Error matcher
