---
status: accepted
---

# A paginator menu re-types the query it picks from

A `MultiPaginator` lets the caller pick the pagination strategy per request, and the page type depends on that pick. A menu's query builder therefore carries a second template, `TPage`, starting as the default arm's page, and each `pageBy<Kind>()` picker replaces it, so `->pageByCursor()->get()->_page()->after` type-checks and the same read without the picker is a static error. The runtime's `Query::repaging()` drops the previous arm's parameters and returns the base `Query<TProj>`, which the generated code narrows with one assertion.

## Considered options

- **One terminal per arm** (`getByCursor()`) needs no second template, but the pick cannot be carried through the chain and every terminal multiplies by the number of arms.
- **A query class per arm** duplicates the whole filter, sort, include and count surface for a difference of one method.
- **Building a fresh query in the picker** infers `TPage` without an assertion but loses `TProj`, undoing every `withX()` already applied.

## Consequences

A menu query cannot share the per-type `withX()` trait, because `static<TProj&Marker>` names one template of a two-template class; its `withX()` methods are emitted inline. A single-strategy query keeps one template.
