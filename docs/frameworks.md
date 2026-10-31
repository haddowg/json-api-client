# Laravel and Symfony

Both bridges ship in this package. Each registers your generated clients as services built over the
framework's own HTTP client, and adds two console commands: `json-api-client:generate` and
`json-api-client:check`. Their framework dependencies are only suggested, so a standalone
application installs none of them.

A client is configured by name with the same keys in both frameworks:

| key | meaning |
|---|---|
| `client` | the generated root client class; its namespace is where the code is generated |
| `base_url` | the server's base URL |
| `token` | sent as `Authorization: Bearer …` on every request |
| `headers` | sent on every request |
| `spec` | the OpenAPI document, as a URL or a file path |
| `output` | the directory the generated code is committed to |
| `target` | `8.3` or `8.4`; defaults to the PHP version running the command |
| `validate` | check every response against `spec`, which must then be a local file |

`spec` and `output` are needed only by the commands, and by `validate`.

## Laravel

The service provider is auto-discovered. Publish the configuration and add a client:

```bash
php artisan vendor:publish --tag=json-api-client-config
```

```php
// config/json-api-client.php
'clients' => [
    'music' => [
        'client' => App\Api\MusicCatalogClient::class,
        'base_url' => env('MUSIC_API_URL'),
        'token' => env('MUSIC_API_TOKEN'),
        'spec' => env('MUSIC_API_URL') . '/openapi.json',
        'output' => base_path('generated/music'),
    ],
],
```

Each client is bound as its own class, so type-hint `MusicCatalogClient` wherever you need it.
`JsonApiClients::client('music')` returns the same instance. For a token that refreshes, register
a header provider, which is called on every request:

```php
$this->app->afterResolving(JsonApiClients::class, function (JsonApiClients $clients) {
    $clients->headersUsing('music', fn () => ['Authorization' => 'Bearer ' . Tokens::fresh()]);
});
```

Requests go through Laravel's `Http` client, so tests use `Http::fake()` and `Http::assertSent()`
exactly as they would for any other outbound call.

## Symfony

Register `haddowg\JsonApiClient\Bridge\Symfony\JsonApiClientBundle` and configure a client:

```yaml
# config/packages/json_api_client.yaml
json_api_client:
    clients:
        music:
            client: App\Api\MusicCatalogClient
            base_url: '%env(MUSIC_API_URL)%'
            token: '%env(MUSIC_API_TOKEN)%'
            spec: '%env(MUSIC_API_URL)%/openapi.json'
            output: '%kernel.project_dir%/generated/music'
```

Each client is a public, autowirable service of its own class, and requests go through the
`http_client` service by way of `Psr18Client`. For a token that refreshes, name an invokable
service as the client's `header_provider`; it is called on every request.

In tests, set `fake: true` under `when@test`. The transport becomes a
`haddowg\JsonApiClient\Testing\FakeTransport` service, which answers from a queue and asserts on
what was sent:

```php
$fake = static::getContainer()->get(FakeTransport::class);
$fake->respondWith(200, ['data' => ['type' => 'albums', 'id' => '1', 'attributes' => ['title' => 'Geogaddi']]]);

// … exercise the code under test …

$fake->assertSent(fn (RequestInterface $r) => $r->getUri()->getPath() === '/albums/1');
```

`FakeTransport` is a plain PSR-18 client, so it works without either framework too: pass it as
`ClientOptions`' transport.

## Generating and checking

`json-api-client:generate [client]` fetches the client's `spec` and runs
`haddowg/json-api-client-codegen` into its `output`, for one client or every configured one. The
codegen is a dev dependency, and the command says how to install it when it is missing.

`json-api-client:check [client]` fails when the committed client was generated from a different
document than the server serves now. The generated `autoload.php` records the sha256 of the
document it came from, so the check fetches the document and compares hashes. It needs no codegen,
so it can run in CI or in a deploy pipeline wherever the application runs.
