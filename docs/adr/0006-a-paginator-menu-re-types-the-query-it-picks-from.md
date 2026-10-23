---
status: accepted
---

# A paginator menu re-types the query it picks from

A `MultiPaginator` lets the caller choose the pagination strategy per request, and the page type a
collection carries depends on that choice. A query builder for a menu therefore carries a second
template, `TPage`, which starts as the default arm's page and which each `pageBy<Kind>()` picker
replaces with its own arm's. `get()` returns `PaginatedCollection<TProj, TPage>`, so
`->pageByCursor()->get()->_page()->after` type-checks and the same read without the picker is a
static error. Picking replaces the page parameters rather than merging them, and the runtime's
`Query::repaging()` does both jobs: it drops the previous arm's members, and it returns the base
`Query<TProj>` so the generated code can narrow it to the new template with one legal assertion.

## Considered options

**One terminal per arm**, such as `getByCursor()`, needs no second template and no assertion, but the choice
cannot be made early and carried through the chain, `first()` and the conditional helpers cannot see
it, and every terminal multiplies by the number of arms.

**A query class per arm** keeps one template each, and duplicates the whole filter, sort, include
and count surface once per arm for a difference of one method.

**Building a fresh query in the picker** with `new self($fetcher, $reader)` infers `TPage` from the
reader without an assertion, but cannot infer `TProj`, which collapses to `object` and undoes every
`withX()` already applied.

## Consequences

A menu query cannot share the per-type `withX()` trait with the projection, because
`static<TProj&Marker>` names one template of a two-template class. Its `withX()` methods are emitted
inline, as `static<TProj&Marker, TPage>`.

A single-strategy query is unchanged: one template, the trait, and `page()` or the cursor methods.
