# json-api-client

A typed PHP client for [JSON:API 1.1](https://jsonapi.org/format/1.1/) services, **generated** from
the OpenAPI 3.1 document your API already serves. Point the codegen at the document, commit what
it writes, and every read and write is checked by PHPStan at level 9: an `include` narrows the
result's type, a capability the server does not offer is a method that does not exist, and the
wire format is read into plain PHP values.

## Lineage

The OpenAPI document is the contract. Any backend built on
[`haddowg/json-api`](https://github.com/haddowg/json-api) serves it: the
[Laravel package](https://github.com/haddowg/json-api-laravel), the
[Symfony bundle](https://github.com/haddowg/json-api-symfony), or the core in your own stack.
This package consumes it.

```
haddowg/json-api                  (core PHP library: framework-agnostic JSON:API)
        │
        ▼
haddowg/json-api-laravel          (framework integrations: each serves the same
haddowg/json-api-symfony           OpenAPI 3.1 document)
        │
        ▼
haddowg/json-api-client-codegen   (dev-only: reads the document, writes the typed client)
haddowg/json-api-client           (this package: the runtime the generated client runs on)
```

[`json-api-ts`](https://github.com/haddowg/json-api-ts) is the TypeScript client for the same
contract, and the two are kept deliberately consistent.

## Packages

| Package | Role |
|---|---|
| `haddowg/json-api-client` | The runtime, the Laravel and Symfony bridges, and the testing fake. Ships to production. |
| [`haddowg/json-api-client-codegen`](https://haddowg.github.io/json-api-client-codegen/) | The CLI that writes the typed client. A dev dependency only. |

!!! tip "The example is the source of truth for API shape"
    [`examples/music-catalog`](example-app.md) is a client generated from the music catalog, the
    API every jsonapi.rest package uses as its worked example. Every snippet in these docs is a
    real call from its [test](../examples/music-catalog/tests/MusicCatalogExampleTest.php), run on
    every build, so it cannot drift from what the codegen writes.

## A 30-second taste

### 1. Generate a client

```bash
composer require haddowg/json-api-client
composer require --dev haddowg/json-api-client-codegen

vendor/bin/json-api-client-codegen https://music.example/docs.json generated/music \
    --namespace='App\Api\Music'
```

Register `generated/music/autoload.php` once under `autoload.files` in composer.json, and commit
the directory. In Laravel or Symfony, `json-api-client:generate` does this
[from your configuration](frameworks.md).

### 2. Create the client

```php
use App\Api\Music\MusicCatalogClient;
use haddowg\JsonApiClient\ClientOptions;

$client = new MusicCatalogClient(new ClientOptions(
    baseUrl: 'https://music.example',
    headers: fn () => ['Authorization' => 'Bearer ' . $tokens->fresh()],
));
```

### 3. One typed read

```php
$albums = $client->albums->query()
    ->withArtist()
    ->fields(['albums' => ['title', 'status', 'artist']])
    ->get();

$albums[0]->title();           // string
$albums[0]->status();          // AlbumStatus::Released
$albums[0]->artist()?->name(); // 'The Beatles': artist() exists because of withArtist()
```

### 4. One typed write

```php
$album = $client->albums->create(new AlbumCreate(
    title: 'Abbey Road',
    status: AlbumStatus::Released,
    artist: Artist::ref('1'),
));

$client->albums->id($album->id())->tracks()->add([Track::ref('7')]);
```

## Where to next

- [Getting started](getting-started.md): install, generate against your own API, and make a first
  call.
- [Concepts](concepts.md): the generated surface, and the rule it follows.
- [Reading](reads.md), [filtering and sorting](filtering-and-sorting.md),
  [writing](writes.md) and [relationships](relationships.md).
- [Custom actions](custom-actions.md) and [atomic operations](atomic-operations.md).
- [Errors](errors.md) and [response validation](validation.md).
- [Laravel and Symfony](frameworks.md): the bridges, configuration and testing.
