# Errors

A JSON:API error response throws, and the class says what happened. Catching by type is how PHP
handles errors, so a `404` arrives as `NotFound` rather than as one error class to interrogate.

```php
try {
    $client->albums->id('999')->get();
} catch (NotFound $e) {
    $e->statusCode();         // 404
    $e->errors()[0]->code;    // 'RESOURCE_NOT_FOUND'
}
```

## Status classes and code classes

Each status has a class: `BadRequest`, `Unauthorized`, `Forbidden`, `NotFound`, `NotAcceptable`,
`Conflict`, `UnsupportedMediaType`, `ValidationFailed`, `TooManyRequests` and `ServerError`, all
under `JsonApiErrorResponse`.

When every error in a response shares one catalogued code whose status matches, the response
throws that code's class instead, which extends its status class. So catching `NotFound` still
catches a `ResourceNotFoundError`, and catching the code's class narrows further:

```php
try {
    $client->albums->id('999')->get();
} catch (ResourceNotFoundError $e) {
    // the code's class: its docblock carries the catalogue's description
}
```

Every code the API's document catalogues, `haddowg/json-api`'s own included, is a generated class in
the client's `errors.php`, named for the code (`INSUFFICIENT_SCOPE` → `InsufficientScopeError`). The
generated client hands the runtime its code map, and the runtime dispatches only through it. A code
the document does not catalogue falls back to the status class, with the raw code still readable.

A code whose catalogue entry declares `meta` has `meta()`, reading the first error carrying it into a
generated value class, each member narrowed to its declared type:

```php
try {
    $client->albums->query()->get();
} catch (InsufficientScopeError $e) {
    $e->meta()->missingScopes; // ['catalog:write'], a non-empty-list<string>
    $e->meta()->include;       // ?string: the include path that needed it, if any
}
```

`meta()` is nullable when the code's `meta` is optional, and throws `MalformedDocument` when a
member the code requires is missing.

Every error response also has `withCode()` and matchers for a range, such as `is4xx()` and
`isUnprocessable()`. Everything this package throws implements `JsonApiClientException`, so one
`catch` covers an error response, a transport failure and a response the client cannot read.

## Mapping errors back to your input

A write's `422` points at the request document (`/data/attributes/title`). The client built that
document, so it maps each pointer back to the member you set: `byPath()` groups errors by it, ready
for a form.

```php
try {
    $client->albums->create(['title' => '']);
} catch (ValidationFailed $e) {
    $e->byPath(); // ['title' => [Error(...)]]
}
```

A nested attribute maps to `releaseInfo.label`, a pivot field to `orderedTracks[0]._pivot.position`,
and an error in an [atomic batch](atomic-operations.md) also carries the `opIndex` of the operation
it blames. The raw `source.pointer` is always still there.

## Errors that are not responses

| Thrown | When |
|---|---|
| `TransportException` | no response arrived at all |
| `CrossOriginRequestException` | a link the server handed back points off the base URL's origin |
| `MalformedDocument` | a response the client cannot read as a JSON:API document |
| `RelationNotIncludedException`, `FieldNotSelectedException` | reading what the response did not carry |
| `UnknownFilterException`, `UnknownSortException`, `UnknownAttributeException`, `UnknownFieldException` | an array door given a name the endpoint does not know |
| `SchemaViolation` | a response that fails [validation](validation.md), when it is on |
