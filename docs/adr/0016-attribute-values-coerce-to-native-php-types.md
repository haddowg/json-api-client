---
status: accepted
---

# Attribute values coerce to native PHP types

`date`, `date-time` and `time` become `DateTimeImmutable`, `number` becomes `float`, `integer` becomes `int`, and an enumerated string becomes a generated enum, mirroring how the server's own fields hydrate the same wire form. This deliberately diverges from the TypeScript client, which passes ISO strings through because JavaScript's `Date` is a serialisation hazard; neither concern applies to `DateTimeImmutable`. Typed write signatures take `DateTimeImmutable`, while the loose array form also accepts ISO strings.
