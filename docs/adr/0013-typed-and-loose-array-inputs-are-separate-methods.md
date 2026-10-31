---
status: accepted
---

# Typed and loose array inputs are separate methods

PHPStan accepts anything for a parameter typed as a union of an array shape and `array<string, mixed>`, because the permissive branch swallows the shape. So each array input comes as a pair: `filter()` takes the generated shape and `filterRaw()` the loose array, and the same split applies to `sort()`/`sortRaw()` and the write forms. Every array door is still guarded at run time against the descriptor's tokens, nested keys included, with a did-you-mean, because filters are all optional and a typo'd optional key compiles (ADR 0003).

## Considered options

Sort tokens are the descriptor's literal strings (`'-releasedAt'`) typed by a PHPDoc union, so argument order is sort precedence and nothing is translated. Sort value objects over a generated enum add a translation layer over a token the wire uses anyway, and per-field `sortByX(desc: true)` methods make precedence implicit in call order.
