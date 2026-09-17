---
status: accepted
---

# Static type narrowing covers `include`, not sparse fieldsets

The TypeScript client makes a read's return type conditional on both `include` and
`fields` via conditional types. PHP has no equivalent, so we verified what PHP's type
system can actually express before designing the generated code. A generated marker
interface per relation, intersected into a generic query builder's template parameter,
gives real narrowing in pure phpdoc generics with no PHPStan extension — proven in both
directions at level 9, including the negative case (`Call to an undefined method
Album&AlbumHasArtist::tracks()`) and, on an 8.4 target, via interface property hooks
(`Access to an undefined property`, plus `Property AlbumHasTitle::$title is not
writable`). We apply it to `include` only. Attributes stay statically present on every
DTO and a sparse fieldset is a wire-level concern enforced at runtime.

## Considered options

Narrowing attributes away with the same mechanism works, and we rejected it for two
reasons. The interface count is dominated by attributes — 75 attributes against 12
relations across the music-catalog spec's 9 types, so 6× the generated surface for the
weaker guarantee — and narrowing is driven by literal arguments, while fieldsets are
routinely assembled programmatically. A non-literal `fields` array degrades to the base
type, so attribute narrowing would have yielded nothing in precisely the case that
motivates sparse fieldsets.

Relations are different in kind, not just cheaper: an included relation is a hydrated
resource and an un-included one is an identifier. The type genuinely changes, which is
the single easiest correctness trap in a JSON:API client to fall into.

## Consequences

Narrowing stops at depth 1. `$album->tracks[0]->album` compiles and throws at runtime if
`tracks.album` was not included, because expressing nested projections needs generic
markers and closure-based nested builders — machinery we judged not worth its debugging
cost. A deliberately untyped `->with(...)` accepting dynamic values sits alongside the
generated `->withX()` methods to serve deep and programmatic includes.

Every absence is therefore a loud runtime throw naming the field and its fix, at every
depth. Static checking is an additive safety net over that floor, never the only guard.

`readonly` cannot be used for selectively-initialized attributes: PHPStan level 9 rejects
it (`property.uninitializedReadonly`). Immutability comes from get-only property hooks on
an 8.4 target, and from method accessors otherwise.
