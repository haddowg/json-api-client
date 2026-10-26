---
status: accepted
---

# A raw action response is typed by a conditional return on `$accept`

From generator contract 10 a custom action can answer in media types other than JSON:API, such as
a CSV export, a PDF or a JSON summary, each with its own schema, and the request's `Accept` picks
one. The generated method takes the choice as a trailing `string $accept`, PHPDoc-typed to the
literal union of the declared media types, and declares its result as a PHPStan conditional on
it: `($accept is 'text/csv' ? string : ($accept is 'application/pdf' ? StreamInterface : …))`.
A call with a literal therefore gets exactly the type of the body it asked for, a media type the
action does not declare is a static error, and the default keeps an existing JSON:API call
unchanged. A request body offered in several media types uses the same idea in the other
direction: a method template bound to the declared media types types `$contentType`, and the
body's `@param` is a conditional on that template. Both were checked against PHPStan level 9
before they were adopted.

## Considered options

**One method per media type**, such as `reportCsv()` and `reportPdf()`, needs no conditional
type, but has to invent a method name from a media type, collides as soon as two types share a
subtype, and leaves the caller unable to choose the media type from a variable.

**A result wrapper** with `asString()`, `asStream()` and `asJson()` is easy to generate and
gives no static guarantee: every accessor exists whichever type was negotiated.

## Consequences

The native return type is the union of every branch, so a call passing a non-literal `$accept`
sees the whole union. That is the honest type for a value chosen at run time.

A JSON body whose schema is an object with members becomes a generated value class with a
readonly property per member and a static `_from()`, narrowed member by member like a tagged
union's branch. Any other JSON body is returned decoded as `mixed`.
