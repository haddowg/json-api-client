---
status: accepted
---

# A form body is a field array the runtime encodes

A multipart body cannot be handed over pre-encoded, because its `Content-Type` must name the boundary the body was built with and the generated method knows only the bare media type. So a `multipart/form-data` or `application/x-www-form-urlencoded` body is a field array (with `FilePart` values for files in multipart), and the runtime encodes it and writes the boundary into the `Content-Type`.

## Considered options

- **A pre-encoded body with a `; boundary=` on `$contentType`** needs no runtime code but puts the wire format on every caller.
- **A third-party PSR-7 multipart stream** adds a runtime dependency for about forty lines of encoding.

## Consequences

The body is read into memory, files included. A caller streaming something large can still send a pre-encoded body through `Request::withRawBody()`.
