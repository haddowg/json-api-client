# CLAUDE.md — executor playbook (json-api-client)

`haddowg/json-api-client` is a **PHP client** for JSON:API 1.1 services, generated from the
service's OpenAPI 3.1 document. It is the PHP counterpart to
[`json-api-ts`](https://github.com/haddowg/json-api-ts), and it targets APIs built with
[`haddowg/json-api`](https://github.com/haddowg/json-api) and its Laravel and Symfony adapters.
This package is the **runtime** plus both framework bridges; the sibling
`haddowg/json-api-client-codegen` produces the generated code this runtime consumes.

The client reads the served OpenAPI document. It does **not** depend on `haddowg/json-api`.

## Where the rest lives

This file carries only the executor-facing residue and links out for the substance:

- [`docs/generated-client.md`](docs/generated-client.md) describes the **generated surface** and
  the runtime under it, including what is designed but not yet built.
- [`docs/adr/`](docs/adr/) carries the **rationale** for decisions that were hard to reverse.
  Several rule out designs that look obviously correct, and each was verified against PHPStan
  level 9. Read them before changing any generated-code shape.
- [`docs/server-prerequisites.md`](docs/server-prerequisites.md) and
  [`docs/json-api-ts-consistency.md`](docs/json-api-ts-consistency.md) track what the client
  needs from the server and how it lines up with the TypeScript client.
- [`CONTEXT.md`](CONTEXT.md) is the glossary.

## Gates (all green before anything lands)

```bash
composer test                            # PHPUnit
vendor/bin/phpstan --memory-limit=1G     # PHPStan level 9
composer cs-check                        # PHP-CS-Fixer, PER-CS 2.0
```

CI enforces all three across PHP 8.3 / 8.4 / 8.5 x lowest/highest. Existing tests are the
contract: satisfy them, never edit a test to pass.

## Conventions

- **Namespace** `haddowg\JsonApiClient\`; PHP `^8.3`. Framework bridges live under
  `Bridge\Laravel\` and `Bridge\Symfony\`, with their framework dependencies in `require-dev`
  plus `suggest`, never in `require`.
- **Generated output targets 8.3 by default and 8.4+ on request.** Method accessors are always
  emitted and are the canonical contract. On an 8.4 target the generator *additionally* emits
  get-only property hooks that delegate to those methods. A hook is a one-line delegation and
  never carries behaviour, which is what makes the two forms impossible to drift.
- **Conventional Commits** for every commit and PR title. PRs are squash-merged and
  release-please drives versioning, so a non-conforming title breaks the release. Mark breaking
  changes with `!` or a `BREAKING CHANGE:` footer. PR descriptions read as external-contributor
  prose, with no internal planning references. Follow `~/.claude/references/commits.md` and
  `~/.claude/references/pull-requests.md`. Rebase with `--force-with-lease`.
- Record architecture decisions as ADRs under `docs/adr/`, following [`docs/adr/ADR-FORMAT.md`](docs/adr/ADR-FORMAT.md).
- **Don't record generator-contract history.** `info.x-generator.contract` is a compatibility
  check, not a changelog. Describe a generated shape as it is now, and never note which contract
  introduced it ("from generator contract 14…") in `CONTEXT.md`, docs, this file or a docblock.
  The server's changelog records when it arrived.

## The rule that governs the generated surface

**Generate exactly what the contract permits, and nothing else.** A capability the server does
not expose is not a runtime error, it is an absent method. This governs `_page()` (absent when
`paginator: "none"`), `related()` and the linkage read (two independent suppression flags),
relationship verbs (a to-one has `set()` and no `add()`), `atomic()`, action signatures, filter
and sort tokens, `withCount()`, and `clientId`.

The consequence to keep in mind: **codegen correctness is client correctness.** A descriptor
misread does not produce a runtime bug, it produces a method that should not exist or omits one
that should. The codegen test suite is the safety net, not the runtime.

## PHPStan footguns (each of these cost real time)

- A `@param` or `@return` tag **must start its own line** in a docblock. Sharing a line with
  description text makes the tag silently invisible, and the native type is used instead.
- `@phpstan-type` aliases are class-local. Without a matching `@phpstan-import-type` in every
  class that references one, the alias degrades to bare `array` with no error reported, and
  every array-shape check is silently lost.
- `@return Generator<int, T>` drops `T`. All four template parameters are required:
  `Generator<int, T, mixed, void>`.
- `readonly` plus selective initialization is rejected at level 9
  (`property.uninitializedReadonly`). Immutability comes from get-only property hooks on an 8.4
  target, and from method accessors otherwise.
- A builder chain whose result is discarded trips `method.resultUnused`. Assign the result or do
  not write the chain. This bit the server repos too.
- **A union of an array shape with `array<string,mixed>` silently accepts anything.** The
  permissive branch swallows the shape. Typed and loose array inputs must be separate methods,
  which is why `filter()`/`filterRaw()` and `create()`/`createRaw()` are pairs.
- CI runs PHPStan on a newer PHP than local dev. Local green is not CI green; trust the PR.
- **The CI `lowest` leg runs mixed Symfony majors.** `illuminate/console ^12` pins
  `symfony/console` to `^7.2` (and drags `symfony/http-kernel` up with it), while `config`,
  `dependency-injection` and `http-client` are constrained only by us and drop to 6.4. So that
  leg resolves 7.2 and 6.4 components side by side, and the bridge tests run there. A failure on
  that leg that reproduces on neither major alone comes from the mix.

## Rules for emitted code (PHPStan cannot see these)

- Read write input with `array_key_exists`, never `??`: `$input['x'] ?? Missing::Value` folds an
  explicit `null` into "absent", so a `PATCH` setting a member to `null` sends nothing.
- Emit a type's `@phpstan-type` aliases and the descriptor constants the runtime guards read on the
  concrete resource class (`Album`), and import them from there wherever they are referenced.
- Mark the update input's constructor and every fluent setter `@phpstan-pure`; without the
  constructor annotation PHPStan reports `possiblyImpure.new` in each setter.
- A generated read class has exactly one erasure point, `ResourceContext::hydrate(): object`, and a
  paginator menu one more, `Query::repaging()`. No bound can satisfy `TProj` and `/** @var TProj */`
  is rejected (`varTag.nativeType`), so emitters narrow through these rather than inventing their
  own workaround.

## Generated runtime code

`src/Exceptions/Codes` is generated from core's error catalogue, never edited by hand. When the
codegen fixture is regenerated from a new server release, regenerate these too:

```bash
php bin/generate-error-codes.php ../json-api-client-codegen/tests/fixtures/music-catalog.openapi.json
```

That rewrites the classes and `tests/Fixtures/error-catalogue.json`. `ErrorCodeParityTest` fails if
the two disagree.

## Working with the siblings

`../json-api-client-codegen` produces what this runtime consumes, so a change to a generated
shape lands there and here together. `../json-api` and its adapters are the server side: when
the client needs a spec change, it goes in the projector first, gets released, and is consumed
here. `docs/server-prerequisites.md` lists the open gaps and which client capability each limits.

`../json-api-ts` is the other client. Consistency between the two is a stated goal, so a
divergence is either recorded in `docs/json-api-ts-consistency.md` with its reason or raised as a
`json-api-ts` issue. Do not let one drift silently.
