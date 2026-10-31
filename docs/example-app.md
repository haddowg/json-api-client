# The example app

[`examples/music-catalog`](../examples/music-catalog/README.md) is a client generated from the music
catalog, the API every jsonapi.rest package uses as its worked example: albums, artists, tracks and
playlists, with filters, sorts, several paginators, pivots, custom actions and atomic operations.

| Path | What it is |
|---|---|
| [`openapi.json`](../examples/music-catalog/openapi.json) | the catalog's OpenAPI document, as `haddowg/json-api` serves it |
| [`generated/`](../examples/music-catalog/generated/client.php) | exactly what `json-api-client-codegen` writes from that document |
| [`src/ExampleServer.php`](../examples/music-catalog/src/ExampleServer.php) | a PSR-18 client that answers from the document's own examples |
| [`tests/MusicCatalogExampleTest.php`](../examples/music-catalog/tests/MusicCatalogExampleTest.php) | every snippet in these docs, run against the generated client |
| [`catalog.php`](../examples/music-catalog/catalog.php) | a small command-line browser |

!!! tip "Two jobs"
    The test is the usage reference: each snippet in these docs is a call from it, checked by
    PHPStan at level 9 and run on every build, so a signature you copy from the docs is one that
    works. The generated code is the answer to "what does the codegen write?": read
    [`resource-albums.php`](../examples/music-catalog/generated/resource-albums.php) to see one type's
    whole surface.

## Running it

```bash
git clone https://github.com/haddowg/json-api-client && cd json-api-client
composer install
php examples/music-catalog/catalog.php
```

```text
Abbey Road by The Beatles (1969)
  1. Come Together
```

## No server required

Every operation in the music catalog's document carries a worked example response, so
`ExampleServer` answers a request by finding the operation it addresses and replying with that
operation's example. On top of that it does just enough to behave like the real thing: the id in
the path becomes the resource's id, an `include` is answered from each related type's own example,
a sparse fieldset is applied, and an atomic batch gets one result per operation.

```php
$psr17 = new Psr17Factory();
$client = new MusicCatalogClient(new ClientOptions(
    baseUrl: 'https://music.example',
    transport: ExampleServer::fromFile(__DIR__ . '/openapi.json', $psr17, $psr17),
    requestFactory: $psr17,
    streamFactory: $psr17,
));
```

Drop the `transport` to point the same client at a running server, such as the
[core library's music catalog example](https://github.com/haddowg/json-api/tree/main/examples/music-catalog).
