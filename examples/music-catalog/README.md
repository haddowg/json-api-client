# The music catalog example

A generated client for the music catalog, the API every jsonapi.rest package uses as its worked
example, and the usage reference the documentation's snippets are taken from.

| Path | What it is |
|---|---|
| `openapi.json` | the music catalog's OpenAPI 3.1 document, as `haddowg/json-api-symfony` serves it (generated, not committed) |
| `generated/` | exactly what `json-api-client-codegen` writes from that document (generated, not committed) |
| `src/ExampleServer.php` | a PSR-18 client that answers from the document's own examples, so nothing needs a server |
| `tests/MusicCatalogExampleTest.php` | every documented snippet, run against the generated client |
| `catalog.php` | a small command-line browser over the same client |

```bash
composer generate-example
php examples/music-catalog/catalog.php
```

## Generating

Neither the document nor the client is committed. `composer generate-example`, which `composer
test` and `composer phpstan` run first, copies `music-catalog.openapi.json` from the
`@haddowg/json-api-symfony-specs` version package.json pins, then runs
[`json-api-client-codegen`](https://github.com/haddowg/json-api-client-codegen) over it:

```bash
npm install                 # needs NODE_AUTH_TOKEN: a GitHub token with read:packages
composer generate-example
```

The package is on GitHub Packages, which needs a token even to read. The codegen is installed into
`tools/codegen`, a Composer project of its own, the first time the script runs and again
whenever `tools/codegen/composer.json` changes.

To generate from documents you exported yourself, such as from a json-api-symfony branch, point
`MUSIC_CATALOG_SPECS` at the directory the bundle's `bin/export-specs.php` wrote:

```bash
php ../json-api-symfony/bin/export-specs.php   # writes ../json-api-symfony/build/specs
MUSIC_CATALOG_SPECS=../json-api-symfony/build/specs composer test
```

The output targets PHP 8.3 so the example runs on every PHP version this package supports; an 8.4
target adds get-only property hooks (`$album->title`) beside the same methods.

A pull request that changes the generated client, through a new pin of the documents or of the
codegen, gets a comment showing the diff. CI fails until the `generated-output` label
acknowledges it.
