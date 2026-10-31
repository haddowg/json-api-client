# Filtering and sorting

A collection read filters and sorts by exactly the vocabulary its endpoint declares. Each comes
with a typed door PHPStan checks and a loose door for input assembled at run time, which the
runtime checks instead.

## Filters

Each filter has a generated `whereX()` method, typed by its value:

```php
$albums = $client->albums->query()
    ->whereTitle('Abbey Road')
    ->whereRating(['min' => 4.0])
    ->sort('-releasedAt', 'title')
    ->page(1, size: 15)
    ->get();
```

That sends `filter[title]=Abbey Road&filter[rating][min]=4&sort=-releasedAt,title&page[number]=1&page[size]=15`.

`filter([...])` takes the same filters as an array, typed by a generated array shape, and
`filterRaw($input)` takes any array. Both check their keys at run time, nested ones included,
because an array shape accepts an unknown optional key:

```php
$client->albums->query()->filterRaw(['titel' => 'Abbey Road']);
// UnknownFilterException: "titel" is not a filter this endpoint accepts. Did you mean "title"?
```

## Sorts

`sort()` takes the endpoint's literal tokens, descending ones prefixed with `-`, in precedence
order. A token the endpoint does not declare is a static error. `sortRaw(list<string>)` is the
loose door, checked at run time.

## Counts

Where an endpoint declares `withCount`, the query has `withCount([...])`, typed by that endpoint's
tokens. A relation's count reads through its relationship object, included or not:

```php
$albums = $client->albums->query()->withCount(['tracks'])->get();
$albums[0]->_rel('tracks')->total(); // ?int
```

The Countable profile goes on `Accept` only when a request counts.

## Relationship Queries

An included to-many can be filtered and sorted by its own vocabulary, the one its related endpoint
declares, under the Relationship Queries profile. `withX()` takes an optional closure over a
generated `<Type><Relation>RelatedQuery`, with the same doors:

```php
$client->albums->query()
    ->withTracks(fn (AlbumTracksRelatedQuery $t) => $t->whereExplicit(false)->sort('trackNumber'))
    ->get();
```

That sends `include=tracks&relatedQuery[tracks][filter][explicit]=false&relatedQuery[tracks][sort]=trackNumber`
and adds the profile to `Accept`. The closure narrows exactly as a plain `withTracks()` does. The
profile has no `page`: the server renders the relation's first page and links the rest.

The same closure filters and sorts the relation read on its own, as that endpoint's plain
parameters:

```php
$tracks = $client->albums->id('1')->tracks()->related(fn (AlbumTracksRelatedQuery $t) => $t->sort('-title'));
```

??? note "Where the closure exists"
    Only where the document permits it: on a to-many whose related read declares a filter or sort,
    reached from a read that declares `relatedQuery`. A write does not accept `relatedQuery`, so the
    projection a write takes has no closure. A single read that does accept it takes an
    `AlbumReadProjection`, which also accepts a write projection.

## Conditionals

`when()`, `unless()` and `tap()` work on every builder. On a query they never narrow the type,
because a runtime condition cannot produce a compile-time one: a relation included conditionally is
read through its `artistRef()` or `hasArtist()` companion, or after a runtime check.
