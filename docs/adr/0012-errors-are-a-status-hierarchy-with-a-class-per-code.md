---
status: accepted
---

# Errors are a status-class hierarchy with a class per catalogued code

PHP handles errors by catching types and the server ships a class per condition, so the client throws typed exceptions rather than the TypeScript client's single `JsonApiError` with matchers. A status class (`NotFound`, `ValidationFailed`, …) under `JsonApiErrorResponse` works against any server and carries transport failures and code-less errors; a class per catalogued code extends its status class. `JsonApiErrorResponse::for()` uses a code class only when every error in the document shares that code and its status matches the response, and otherwise falls back to the status class carrying every error, because the catalogue is open and applications throw codes the projector never saw.

## Considered options

A hand-maintained code-to-exception table would have shipped sooner, but it drifts silently against the server's exception set (see ADR 0005).
