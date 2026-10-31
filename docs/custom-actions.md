# Custom actions

An API's own operations beyond create, read, update and delete hang off `actions()`: on the type
accessor for a collection action, and on an id-handle for an action on one resource. A namespace of
their own means an action's name never collides with a relation or a verb.

```php
$summary = $client->albums->actions()->summary();
$summary['albums']['total']; // 15

$csv = $client->albums->actions()->export(accept: 'text/csv'); // 'id,title,…'
$album = $client->albums->id('1')->actions()->reissue(['title' => 'Abbey Road (Anniversary)']);
```

## What each action looks like

Each action's signature follows what its operation declares:

| The action declares | The generated method takes or returns |
|---|---|
| no input | no parameters |
| a raw body | `StreamInterface\|string $body` |
| a JSON:API document | the three [write input forms](writes.md#three-input-forms) for that type |
| a form body | a field array, typed as an array shape when the schema lists its fields |
| no output | `void` |
| a meta result | `array<string, mixed>` |
| a JSON:API document | the resource, or a collection of it |
| a URL to fetch | `ActionLocation`, alone or beside the document result |

An action answering in several media types takes a trailing `$accept`, and one accepting several a
leading `$contentType`, each typed by a PHPStan conditional: binary bodies are a `StreamInterface`,
text a `string`, and a JSON object a generated value class.

??? note "Going deeper: form bodies and URL answers"
    A form body is a field array the runtime encodes as multipart or URL-encoded. A `FilePart`
    carries a file's bytes, filename and media type; a boolean is sent as `1` or `0`; a list is sent
    as one `name[]` part per item.

    An action that answers with a URL returns an `ActionLocation` (`url`, `type` and a nullable
    `expiresAt`), read from the document's `links.location`. The client never follows it.

An action's `summary` and `description` become its method's docblock, and every generated method
lists the error classes its operation can raise under `@throws`.
