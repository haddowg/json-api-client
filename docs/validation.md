# Response validation

By default the client checks responses structurally: that the body is a JSON:API document, and that
each resource carries `type` and `id`. Opting in checks every successful response against the schema
the server's own OpenAPI document gives that response, so a server that breaks its contract fails
loudly instead of handing back a wrong value.

```php
use haddowg\JsonApiClient\Validation\OpenApiValidator;

$options = new ClientOptions(baseUrl: 'https://music.example');
$client = new MusicCatalogClient($options->withValidator(OpenApiValidator::fromFile(__DIR__ . '/openapi.json')));

$client->albums->id('1')->get();
// SchemaViolation: The 200 response to GET /albums/1 does not match its documented schema:
//   /data/attributes: The required properties (averageRating, artwork, …) are missing
```

Validation needs [`opis/json-schema`](https://opis.io/json-schema/), which this package suggests
rather than requires:

```bash
composer require opis/json-schema
```

## What is checked

The operation is found by method and path template, the response by its status (then `2XX`, then
`default`), and the schema by media type. `SchemaViolation` lists each failing JSON pointer with what
is wrong there.

- A request carrying a sparse fieldset is checked without the read schema's `required` lists,
  because the server leaves members out on purpose. Every member that is present is still checked.
- A response the document does not describe is not checked: an unknown path, status or media type,
  or an empty body. The point is to catch a server breaking its own contract, not one that has
  grown beyond the copy of the document you hold.

Keep the document beside the generated client, so the two match. The
[Laravel and Symfony bridges](frameworks.md) switch validation on with a client's `validate` key,
reading its `spec` file.

`ResponseValidator` is the interface the transport calls, so anything else that can judge a body can
stand in for `OpenApiValidator`.
