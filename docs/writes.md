# Writing

`create()` on the type accessor and `update()` and `delete()` on an id-handle always execute and
return what the server answered with.

```php
$album = $client->albums->create(new AlbumCreate(title: 'Abbey Road'));
$album = $client->albums->id('1')->update(Album::update()->title('Abbey Road (Remastered)'));
$client->albums->id('1')->delete();
```

## Three input forms

A write accepts its input in any of three forms. They exist because no single form is enough at
PHPStan level 9, and their blind spots do not overlap.

=== "Named arguments"

    The form PHPStan checks completely: a missing required member, an unknown one and a wrong
    value type are all static errors.

    ```php
    $album = $client->albums->create(new AlbumCreate(
        title: 'Abbey Road',
        status: AlbumStatus::Released,
        artist: Artist::ref('1'),
    ));
    ```

=== "Fluent builder"

    For assembly that is conditional. A missing required member is checked when the builder is
    built, and throws `MissingRequiredMemberException`.

    ```php
    $client->albums->create(
        Album::create()
            ->title('Abbey Road')
            ->when($released, fn ($b) => $b->status(AlbumStatus::Released)),
    );
    ```

=== "Array"

    For input assembled at run time. The array shape is typed, and an unknown key, which an array
    shape cannot catch, throws `UnknownAttributeException` with a did-you-mean.

    ```php
    $client->albums->create(['title' => 'Abbey Road', 'status' => 'released']);
    ```

All three produce the same named-argument DTO, so there is one serialisation path. A create whose
type takes a client-generated id has an `id` argument; one that forbids it has none.

## Updates, and absent versus null

Every update member is optional. `Album::update()` returns an empty `AlbumUpdate` whose setters each
return a new input, so a setter whose result is discarded is a PHPStan error rather than a silent
no-op. A member never set is not sent; a member set to `null` is sent and clears the server value.

```php
$client->albums->id('1')->update(Album::update()->title('Abbey Road (Remastered)'));
$client->albums->id('1')->update(new AlbumUpdate(availableUntil: null)); // clears availableUntil
```

## Shaping the response

A write takes an optional projection, as a closure or a reusable object. It offers only `withX()`,
`with()` and `fields()`, because a write's response honours `include` and `fields` and nothing
else:

```php
$album = $client->albums->create($input, fn ($p) => $p->withArtist()); // AlbumBase&AlbumHasArtist
$album->artist()?->name();
```

## Relations in a write

A relation setter takes the related resource, a bare reference from `Artist::ref('1')`, or `null`
to clear a to-one. In an [atomic batch](atomic-operations.md) it also takes a resource created
earlier in the same batch.

## Next

- [Relationships](relationships.md): writing one relationship on its own.
- [Errors](errors.md): what a refused write throws, and mapping its errors back to your input.
