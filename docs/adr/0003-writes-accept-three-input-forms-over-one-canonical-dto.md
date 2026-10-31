---
status: accepted
---

# Writes accept three input forms over one canonical DTO

`create()` and `update()` accept an array shape, a named-argument DTO, or a fluent builder, because measured at PHPStan level 9 no single form is adequate and their blind spots do not overlap: an all-optional array shape (a `PATCH` body) accepts a typo'd key, a builder cannot statically require a setter call, and only an array accepts programmatically assembled input. The named-argument DTO catches everything, so it is canonical: the builder's `build()` and the array's generated `from()` both produce it, giving one serialisation path.

## Consequences

- Runtime guards backstop the static gaps: `from()` rejects unknown keys with a did-you-mean and `build()` rejects a missing required member.
- Optional DTO members default to the `Missing` sentinel, since PHP cannot express "argument not passed", so absent stays distinct from an explicit `null`.
- The forms are reached as static factories on the resource class (`Album::create()`, `Album::create([…])`), which return inert values, so no overload performs IO and a forgotten submit cannot silently no-op.
- An update has no required member, so `Album::update()` returns an empty `AlbumUpdate` whose `@phpstan-pure` setters each return a new input rather than a separate builder. A discarded setter call is `method.resultUnused` under PHPStan and silently lost without it.
