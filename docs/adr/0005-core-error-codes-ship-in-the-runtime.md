---
status: accepted
---

# Core's error codes ship in the runtime, generated and pinned by a parity test

Every API built on `haddowg/json-api` serves the same core error catalogue, 50 codes in 1.1.0, so
generating a class per code into every API's client would emit the same 50 files over and over.
The runtime ships them instead, in `src/Exceptions/Codes`, and a per-API run generates only the
codes the installed runtime lacks: those an application contributes, or those a server newer
than the client already emits. An unknown code still falls back to its status class, so nothing
breaks while the runtime catches up.

## Considered options

A hand-maintained code-to-exception table was rejected when the error design was settled,
because it drifts from core's exception set silently. Shipping the classes in the runtime does
not reintroduce that table: the classes are still generated, by `bin/generate-error-codes.php`
from an OpenAPI document, and `ErrorCodeParityTest` regenerates them from a committed copy of the
catalogue and fails if `src/` differs. A stale class, a hand edit, or a catalogue updated without
regenerating all turn CI red.

## Consequences

The runtime's catalogue is only as current as its last regeneration, which happens whenever the
codegen fixture is refreshed from a new server release.
