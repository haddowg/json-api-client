---
status: accepted
---

# A raw action response is typed by a conditional return on `$accept`

A custom action can answer in media types other than JSON:API, each with its own schema, and the request's `Accept` picks one. The generated method takes the choice as a trailing `string $accept` typed to the literal union of the declared media types, and declares its result as a PHPStan conditional on it, so a literal call gets exactly the body type it asked for and an undeclared media type is a static error. A request body offered in several media types does the same in the other direction, with a method template on `$contentType` keying the body's `@param`.

## Considered options

- **One method per media type** (`reportCsv()`) must invent names from media types, collides when two share a subtype, and cannot take the choice from a variable.
- **A result wrapper** with `asString()`/`asStream()`/`asJson()` gives no static guarantee: every accessor exists whatever was negotiated.

## Consequences

A non-literal `$accept` sees the union of every branch, which is the honest type for a value chosen at run time.
