---
status: accepted
---

# An absent capability is an absent method, not a runtime guard

The generated client exposes exactly what the server's document permits: `_page()` exists only on a paginated collection, a to-one relation has `set()` and no `add()`, the related and linkage reads are generated only where their endpoints exist, `atomic()` only when the server advertises the atomic extension, an id parameter only as the type's `clientId` allows, and a loose door such as `filterRaw()` only where the endpoint declares that parameter (the runtime keeps them `protected` and generated builders re-declare them public). A disabled endpoint called from code that compiled is a 404 at run time; an absent method is a static error. The TypeScript client threads some of these as descriptors and throws at run time instead.

## Consequences

Codegen correctness is client correctness: a descriptor misread produces a method that should not exist, or omits one that should, rather than a wrong value.
