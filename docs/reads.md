# Reading

Every type has a root accessor on the client. A collection is read through its query builder, one
resource through its id-handle, and every read returns the generated resource class.

```php
$albums = $client->albums->query()->get();   // a collection
$album = $client->albums->id('1')->get();    // one album
```

Nothing is sent until a terminal call: `get()` or `first()` on a query, `get()` on an id-handle.

## Includes narrow the type

`withArtist()` includes the `artist` relation and narrows the result to
`AlbumBase&AlbumHasArtist`, so `artist()` type-checks after it and is a static error without it:

```php
$albums = $client->albums->query()
    ->withArtist()
    ->fields(['albums' => ['title', 'status', 'artist']])
    ->get();

$albums[0]->artist()?->name(); // 'The Beatles'
```

A single read takes the same shaping as a closure, or as a projection built once and reused:

```php
$album = $client->albums->id('1')->get(fn ($p) => $p->withArtist()->withTracks());

$withEverything = Album::projection()->withArtist()->withTracks();
$album = $client->albums->id('1')->get($withEverything);
```

Narrowing stops at one hop. A deeper path (`tracks.album`) or an include list assembled at run
time goes through `with(...)`, which includes without narrowing.

!!! note "The runtime backs the types up"
    Reading a relation the response did not include throws `RelationNotIncludedException`, at any
    depth, naming the relation and the call that would include it. It never answers `null`,
    because "not included" and "no artist" are different answers. `artistRef()` and `hasArtist()`
    read the linkage, which is there either way, and never throw:

    ```php
    $album = $client->albums->id('1')->get();
    $album->artistRef()?->id; // '1', without including the artist
    ```

## Sparse fieldsets

`fields()` restricts what comes back on the wire, by type. Every attribute is still declared on the
class, and reading one the fieldset left out throws `FieldNotSelectedException` rather than
returning `null`. The type's own members are checked when you call `fields()`:
`UnknownFieldException` names a member the type does not have.

## Attribute values

Values arrive as native PHP types: dates and times as `DateTimeImmutable`, `number` as `float`,
`integer` as `int`, and an enumerated string as a generated enum.

```php
$album->releasedAt()?->format('Y'); // '1969'
$album->status();                   // AlbumStatus::Released
$album->releaseInfo()['label'] ?? null; // 'Apple Records': an object attribute is a typed array shape
```

## Collections and pagination

A collection read returns a `ResourceCollection<T>`, iterable and countable, with `_meta()` and
`_links()`. A type that paginates returns a `PaginatedCollection<T, TPage>` instead, which adds
`_page()`, `_next()`, `_prev()` and `_autoPaging()`, a lazy generator over every remaining page.
`TPage` is the exact strategy (`PageNumber`, `Offset`, `FixedPage` or `Cursor`), so a cursor's
`after` is a static error on a page-numbered type:

```php
$albums = $client->albums->query()->page(1, size: 15)->get();

$albums->_page()->number;  // int
$albums->_page()->total;   // ?int, set when the read counted itself
foreach ($albums->_autoPaging() as $album) {
    // every album, fetching each page as it is reached
}
```

??? note "Going deeper: a menu of strategies"
    An endpoint can offer several strategies under a `page[kind]` discriminator. The query starts
    typed as the default strategy's page and sends no page parameters; a `pageBy<Kind>()` picker
    chooses another and re-types the query:

    ```php
    $client->tracks->query()->get();                         // PaginatedCollection<TrackBase, PageNumber>
    $client->tracks->query()->pageByCursor(size: 25)->get(); // PaginatedCollection<TrackBase, Cursor>
    ```

    Parameters keep their role names (`$number`, `$size`) whatever the server calls the key.

## The document, links and meta

Every resource reads what the client adds through accessors that start with an underscore:
`_meta()`, `_links()`, `_link('reissue')`, `_self()` and `_raw()` for the resource object as it
arrived. `_document()` is the response every resource in it shares, and its `jsonapi()` says which
extensions and profiles the server applied.

A link read (`_self()`, `_link()`, a page's `next()`) accepts both a plain string and a
`{href, describedby}` link object. The links a type declares by name are typed through
`_declaredLinks()`:

```php
$image->_declaredLinks()->download()?->meta()->expiresAt(); // ?\DateTimeImmutable
$image->_declaredLinks()->thumbnail();                      // ?string
```

## Next

- [Filtering and sorting](filtering-and-sorting.md), including counts and Relationship Queries.
- [Relationships](relationships.md): reading a relation on its own.
