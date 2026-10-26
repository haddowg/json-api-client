---
status: accepted
---

# A form body is a field array the runtime encodes

From generator contract 11 a custom action can accept a `multipart/form-data` or
`application/x-www-form-urlencoded` body beside its other request media types. A multipart body
cannot be handed over pre-encoded: its `Content-Type` must name the boundary the body was built
with, and the generated method only knows the bare media type. So the generated method takes a
field array, `array<string, string|int|float|bool|FilePart>` for multipart and
`array<string, string|int|float|bool>` for urlencoded, and the runtime encodes it, generating the
boundary and writing it into the `Content-Type`. A `FilePart` is a file upload with a filename and
its own media type; a stream on its own would not say whether it is a file or a field.

## Considered options

**Accept a pre-encoded body with a `; boundary=` parameter on `$contentType`** needs no runtime
code, but puts the wire format on every caller and makes the boundary their problem.

**Use a PSR-7 multipart stream from a third-party package** brings a dependency into the runtime
for about forty lines of encoding.

## Consequences

The body is read into memory before it is sent, files included. That suits the uploads custom
actions take; a caller streaming something large can still send a pre-encoded body through
`Request::withRawBody()`.
