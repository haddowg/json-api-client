---
status: accepted
---

# Static type narrowing covers `include`, not sparse fieldsets

PHP has no conditional types, so a read's `include` is narrowed by intersecting a generated marker interface per relation (`AlbumHasArtist`) into the query builder's template, which PHPStan checks at level 9 with no extension. We narrow `include` only, at depth 1, because an included relation changes type (hydrated resource versus identifier) while a sparse fieldset only changes what arrives on the wire. Every attribute is statically present on the DTO, and reading one a fieldset excluded, or a relation that was not included, throws a runtime exception naming the fix.

## Considered options

- **Narrowing attributes too.** It works, but attributes outnumber relations about six to one in generated interfaces, and fieldsets are usually built programmatically, where a non-literal array degrades to the base type and the narrowing yields nothing.
- **Nested narrowing.** It needs generic markers and closure-based nested builders; deep and programmatic includes go through an untyped `with(...)` instead, checked at runtime.
- **Lazy-fetching an un-included relation.** Deferred: a hidden HTTP call per access inside a loop is the classic way to melt an API.
