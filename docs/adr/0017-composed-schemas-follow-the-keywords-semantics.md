---
status: accepted
---

# Composed attribute schemas follow each keyword's own semantics

A `oneOf` with a `discriminator` becomes a tagged union of generated branch classes, narrowed with `instanceof`/`match`. An `anyOf` becomes one merged shape with every branch member optional, because `anyOf` is not exclusive: a value can satisfy two branches, so a union of branch classes would be a type that lies about a document the server can send. An `allOf` is flattened and merged, and a `{"type": "null"}` branch becomes nullability rather than a branch class.
