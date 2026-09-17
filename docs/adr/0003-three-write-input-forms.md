---
status: accepted
---

# Writes accept three input forms over one canonical DTO

`create()` and `update()` accept an array shape, a named-argument DTO, or a fluent
builder. Three front doors is more surface than we wanted, and we adopted it because
measurement showed no single form is adequate: each has a different blind spot, and the
blind spots do not overlap. The named-argument DTO is canonical internally — the builder's
`build()` produces one, and the array passes through a generated `from()` — so there is one
serialisation path regardless of which door a caller used.

## The measurements

Checked against PHPStan level 9 on PHP 8.4:

| | missing required | typo on required key | typo on **optional** key | wrong value type | absent vs null |
|---|---|---|---|---|---|
| array shape | static | static | **runtime** | static | native |
| named-arg DTO | static | static | static | static | `Missing` sentinel |
| fluent builder | **runtime** | static | static | static | native |

An array shape inside a union parameter is still fully checked, so combining the forms
costs nothing on any branch. But an all-optional shape — which is exactly what a JSON:API
`PATCH` body is — accepts any unknown key, so `['titel' => 'x']` compiles, sends nothing,
and silently no-ops. A builder cannot statically require that a setter was called. Only the
DTO catches everything, and only the array accepts programmatically-assembled input at all
(`array<string,mixed>` is rejected against a shape, so the raw path is explicit and visible
at the call site).

Docs therefore lead with the DTO, the builder is for conditional assembly, and the array is
for programmatic input.

## Consequences

Runtime guards must backstop the static gaps, not merely duplicate them: `from()` rejects
unknown keys with a did-you-mean, and `build()` rejects a missing required member. Without
those, the array form's optional-key blind spot is a silent data-loss bug.

`Missing` is a sentinel enum defaulting every optional DTO member, because PHP has no way
to express "argument not passed" — so optional members are typed `string|Missing` rather
than `string`. It is never passed by a caller, but it is visible in signatures.

The builders are reached as static factories on the resource class (`Album::create()`,
`Album::create([…])`) where the arity overload returns an inert value either way, so no
overload performs IO and a forgotten submit cannot silently no-op. That places two plain
reserved names on the resource class, requiring build-time collision detection against
attributes named `create` or `update` (see ADR 0002).
