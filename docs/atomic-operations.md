# Atomic operations

When the server advertises the Atomic Operations extension, the client has an `atomic()` method:
several writes, sent as one request, that commit together or not at all.

```php
[$artist, $album] = $client->atomic(function (Tx $tx): array {
    $artist = $tx->create(Artist::create()->name('Boards of Canada'));
    $album = $tx->create(Album::create()->title('Geogaddi')->artist($artist));

    return [$artist, $album];
});

$artist->id();               // the id the server assigned
$album->resource()->title(); // 'Geogaddi'
```

The callback receives a recorder, `Tx`. Its `create()`, `update()` and `delete()` each record one
operation, in call order; nothing is sent until the callback returns. `atomic()` returns whatever
the callback returned, typed as it returned it, so the handles come back exactly where you put
them.

## Handles

`create()` takes a create input or builder and returns a `Created<T>`; `update()` takes a target and
an update input and returns an `Updated<T>`; `delete()` takes a target. After the batch commits,
each handle's `resource()` reads its operation's result, and `meta()` its result's meta. Reading one
before then throws.

An update the server applied exactly as sent may come back without a resource, which the extension
allows, so `Updated::resource()` is nullable.

## Referring to something created in the same batch

A create carries a local id (`atomic-<index>`) unless its input has a client-generated id, so its
handle can stand in for the new resource anywhere a later operation needs it: as an update's or a
delete's target, and in a relation setter. On the wire, the album above points at its artist as
`{"type": "artists", "lid": "atomic-0"}`.

A relation setter accepts a `Created` handle of the related type only, so passing an album handle
where an artist belongs is a static error.

## When a batch is refused

Nothing was committed, and `atomic()` throws the status or code class for the response. Each error's
`opIndex` names the operation it blames, in call order, and its `path` the input member within it,
so [`byPath()`](errors.md#mapping-errors-back-to-your-input) groups a validation failure as it would
for a standalone write.
