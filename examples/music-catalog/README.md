# The music catalog example

A generated client for the music catalog, the API every jsonapi.rest package uses as its worked
example, and the usage reference the documentation's snippets are taken from.

| Path | What it is |
|---|---|
| `openapi.json` | the music catalog's OpenAPI 3.1 document, as `haddowg/json-api` serves it |
| `generated/` | exactly what `json-api-client-codegen` writes from that document |
| `src/ExampleServer.php` | a PSR-18 client that answers from the document's own examples, so nothing needs a server |
| `tests/MusicCatalogExampleTest.php` | every documented snippet, run against the generated client |
| `catalog.php` | a small command-line browser over the same client |

```bash
php examples/music-catalog/catalog.php
```

## Regenerating

When the document or the codegen changes, regenerate with
[`haddowg/json-api-client-codegen`](https://github.com/haddowg/json-api-client-codegen) (here from
a sibling checkout, run at this repository's root) and commit the result:

```bash
php ../json-api-client-codegen/bin/json-api-client-codegen \
    examples/music-catalog/openapi.json examples/music-catalog/generated \
    --namespace='haddowg\JsonApiClient\Examples\MusicCatalog' --target=8.3
```

The output targets PHP 8.3 so the example runs on every PHP version this package supports; an 8.4
target adds get-only property hooks (`$album->title`) beside the same methods.
