---
status: accepted
---

# Core's error codes ship in the runtime, generated and pinned by a parity test

Every API built on `haddowg/json-api` serves the same core error catalogue, so generating a class per core code into every API's client would emit the same files over and over. The runtime ships them in `src/Exceptions/Codes`, and a per-API run generates only the codes the installed runtime lacks: those an application contributes, or those a newer server emits. The classes are still generated, never hand-written, by `bin/generate-error-codes.php`, and `ErrorCodeParityTest` regenerates them from a committed catalogue and fails if `src/` differs, so the hand-maintained table this replaces cannot drift back in.

## Consequences

The runtime's catalogue is only as current as its last regeneration. An unknown code falls back to its status class, so nothing breaks while it catches up.
