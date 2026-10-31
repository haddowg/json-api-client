# Getting started

From nothing to a first typed read and write. You generate a client from your API's OpenAPI
document, create it once, then call it.

!!! note "Prerequisites"
    PHP 8.3 or later, and an API built on [`haddowg/json-api`](https://github.com/haddowg/json-api)
    that serves its OpenAPI 3.1 document, usually at `/docs.json`. No API yet? Generate from the
    [music catalog document](../examples/music-catalog/openapi.json) the example uses.

## 1. Install

The runtime ships to production. The codegen is a dev dependency: the code it writes depends on
the runtime alone.

```bash
composer require haddowg/json-api-client
composer require --dev haddowg/json-api-client-codegen
```

The runtime talks to your server through any PSR-18 HTTP client. With `php-http/discovery` and an
installed client (Guzzle, Symfony's `Psr18Client`, …) it is found for you; otherwise pass one in
`ClientOptions`. The [Laravel and Symfony bridges](frameworks.md) use the framework's own HTTP
client.

## 2. Generate

```bash
vendor/bin/json-api-client-codegen https://music.example/docs.json generated/music \
    --namespace='App\Api\Music'
```

That writes one file per resource type, plus `client.php`, `enums.php` and an `autoload.php` class
map. Register the map once and commit the directory:

```json
{
    "autoload": {
        "files": ["generated/music/autoload.php"]
    }
}
```

Regenerate whenever the API changes; `--check` exits non-zero when the committed client no longer
matches the document, which makes it a CI drift gate. The
[codegen's documentation](https://haddowg.github.io/json-api-client-codegen/) has every flag.

## 3. Create the client

The root client is named after the namespace's last segment, `MusicCatalogClient` here, and takes
`ClientOptions`:

```php
use haddowg\JsonApiClient\ClientOptions;

$client = new MusicCatalogClient(new ClientOptions(
    baseUrl: 'https://music.example',
    headers: fn () => ['Authorization' => 'Bearer ' . $tokens->fresh()],
));
```

`headers` is called on every request, so a token that expires is refreshed without rebuilding the
client. The client follows a URL the server hands back, such as a `next` link, only when it is on
the base URL's origin, because those headers go on every request.

## 4. Read

```php
$albums = $client->albums->query()
    ->withArtist()
    ->fields(['albums' => ['title', 'status', 'artist']])
    ->get();

foreach ($albums as $album) {
    echo $album->title(), ' by ', $album->artist()?->name();
}

$album = $client->albums->id('1')->get();
```

`withArtist()` includes the artist and narrows the type, so `artist()` type-checks only after it.
See [reading](reads.md).

## 5. Write

```php
$album = $client->albums->create(new AlbumCreate(
    title: 'Abbey Road',
    status: AlbumStatus::Released,
    artist: Artist::ref('1'),
));

$client->albums->id('1')->update(Album::update()->title('Abbey Road (Remastered)'));
$client->albums->id('1')->tracks()->add([Track::ref('7')]);
$client->albums->id('1')->delete();
```

See [writing](writes.md) and [relationships](relationships.md).

## Next

- [Concepts](concepts.md): what was generated, and why some methods are missing on purpose.
- [The example app](example-app.md): the same client, generated from the music catalog and run end
  to end.
