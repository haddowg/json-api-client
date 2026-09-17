# haddowg/json-api-client

A typed PHP client for [JSON:API 1.1](https://jsonapi.org/format/1.1/) services, generated
from your API's OpenAPI 3.1 document.

[![CI](https://github.com/haddowg/json-api-client/actions/workflows/ci.yml/badge.svg)](https://github.com/haddowg/json-api-client/actions/workflows/ci.yml)

> **Part of the [jsonapi.rest](https://jsonapi.rest) suite**, a spec-compliant JSON:API 1.1
> stack for PHP bound together by one conformance-tested OpenAPI 3.1 contract.

Point the generator at a served OpenAPI 3.1 document and you get one class per resource type,
a query builder PHPStan checks at level 9, and a runtime that handles content negotiation,
compound documents and atomic operations. Asking for a relation you never included is a static
error at depth one and a runtime throw deeper, never a silent null.

## Status

**Not yet released.** The design is settled, the code is not. Nothing is on Packagist yet.
[`CONTEXT.md`](CONTEXT.md) records the resolved decisions and [`docs/adr/`](docs/adr/) the
rationale behind the ones that are hard to reverse.

## How the pieces fit

This package is the runtime and both framework bridges, and it ships in production.
[`haddowg/json-api-client-codegen`](https://github.com/haddowg/json-api-client-codegen) reads
the OpenAPI document and emits the code this runtime consumes. It is a dev-only CLI, so the
spec parser and emitter never reach a production autoload map.

On the server side, [`haddowg/json-api`](https://github.com/haddowg/json-api) and its
[Symfony bundle](https://github.com/haddowg/json-api-symfony) and
[Laravel package](https://github.com/haddowg/json-api-laravel) publish the contract this client
reads. [`json-api-ts`](https://github.com/haddowg/json-api-ts) is the TypeScript client, and
the two are kept deliberately consistent.

The client needs the OpenAPI document and nothing else. It does not depend on
`haddowg/json-api`, so it works against any JSON:API 1.1 service that serves one.

## Requirements

PHP 8.3, 8.4 or 8.5, plus a PSR-18 HTTP client and PSR-17 factories. The Laravel and Symfony
bridges use the framework's own HTTP stack, so an app on either needs neither.

## License

MIT. See [LICENSE](LICENSE).
