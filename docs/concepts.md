# Concepts

## The rule: an absent capability is an absent method

The generated client exposes exactly what the server's document permits. A to-one relation has
`set()` and no `add()`; a collection that does not paginate has no `_page()`; `atomic()` exists only
when the server advertises the Atomic Operations extension; a filter, sort or count token the
endpoint does not declare is not in the method's type. Calling a disabled endpoint from code that
compiled would be a 404 at run time. An absent method is a static error instead.

Static types cover what they can, and the runtime backs them where they cannot. Reading a relation
that was not included throws `RelationNotIncludedException`, at any depth, naming the relation and
the fix; reading an attribute a sparse fieldset left out throws `FieldNotSelectedException`; an
unknown filter name passed through an array throws `UnknownFilterException` with a did-you-mean.
Nothing is silently `null`.

## The generated surface

For each resource type, using `albums` from the music catalog:

| Generated | What it is |
|---|---|
| `$client->albums` (`Albums`) | the type accessor: lists, creates and reaches one album by id |
| `$client->albums->id('1')` (`AlbumHandle`) | the id-handle: get, update, delete, relationships and actions, without fetching anything first |
| `Album`, over `AlbumBase` | the resource class every read returns, declaring every attribute and relation |
| `AlbumHasArtist`, `AlbumHasTracks` | relation markers an include intersects into the result's type |
| `AlbumQuery` | the collection read: includes, fieldsets, filters, sorts, counts, pagination |
| `AlbumProjection`, `AlbumReadProjection` | what a write's or a single read's response brings back: includes and fieldsets |
| `AlbumCreate`, `AlbumCreateBuilder`, `AlbumUpdate` | the write inputs |
| `AlbumArtistRelation`, `AlbumTracksRelation` | a relationship's reads and the verbs it permits |
| `AlbumTracksRelatedQuery` | how a to-many is filtered and sorted from a read |
| `AlbumActions`, `AlbumCollectionActions` | the custom actions |

## Reserved accessors

An API's attributes and relations are plain methods (`$album->title()`, `$album->artist()`).
Everything the client adds starts with an underscore, which a JSON:API member name never can, so
the two can never collide: `_meta()`, `_links()`, `_link()`, `_self()`, `_raw()`, `_rel()`,
`_document()`, `_declaredLinks()`, `_handle()`, and on a collection `_page()`, `_next()` and
`_autoPaging()`.

## Methods, and on PHP 8.4 properties

Accessors are methods. Generated for a PHP 8.4 target, each is also a get-only property that
delegates to its method, so `$album->title` and `$album->title()` are the same read:

```php
$album->artist?->name; // 8.4 target
$album->artist()?->name(); // either target
```

## The document is the contract

The client reads only the served OpenAPI document; it does not depend on `haddowg/json-api`. The
document's `info.x-generator.contract` integer tells the codegen which structures to expect.
[Server prerequisites](server-prerequisites.md) lists what the client reads from the document and
what it still cannot.
