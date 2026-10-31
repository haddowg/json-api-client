# Relationships

Each relation of an id-handle has a method exposing exactly the verbs its endpoints permit: a to-one
has `set()` and no `add()`, and a read is generated only where its endpoint exists.

```php
$album = $client->albums->id('1');

$album->artist()->related();  // ?Artist: the related resource
$album->artist()->get();      // ?Identifier: the linkage alone
$album->artist()->set(Artist::ref('2'));

$album->tracks()->related();  // the tracks, a page at a time
$album->tracks()->add([Track::ref('7')]);
$album->tracks()->remove([Track::ref('7')]);
$album->tracks()->replace([Track::ref('1'), Track::ref('2')]);
```

A to-many's related read filters and sorts by the relation's own vocabulary, through the same
closure a [Relationship Query](filtering-and-sorting.md#relationship-queries) takes:

```php
$tracks = $client->albums->id('1')->tracks()->related(fn (AlbumTracksRelatedQuery $t) => $t->sort('-title'));
```

## Writing to a resource you just read

A resource read through the client reaches its id-handle with `_handle()`, so a write to it needs
no id bookkeeping and offers the same verbs:

```php
$client->albums->id('1')->get()->_handle()->artist()->set(Artist::ref('2'));
```

## Relationship counts

A relation's count, where the read asked for it with `withCount`, reads through `_rel()` whether or
not the relation was included:

```php
$album->_rel('tracks')->total(); // ?int
```

## Pivot data

A relation that carries fields on the link itself, such as a playlist's ordered tracks with a
`position`, writes through a generated edge class with only the fields a write may set, and reads
them through `<relation>Pivots()` on the parent:

```php
$client->playlists->id($id)->orderedTracks()->replace([
    new PlaylistOrderedTracksEdge(Track::ref('4'), position: 1),
]);

foreach ($playlist->orderedTracksPivots() as $pivot) {
    $pivot->_identifier()->id; // the track the pivot belongs to
    $pivot->position();
}
```

A pivot field the server always renders reads strictly; any other reads as nullable.
