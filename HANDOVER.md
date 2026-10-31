# Handover — jsonapi.rest PHP client suite

State as of 2026-10-21. Written for an agent picking this up cold.

**Read these first, in order.** This document is a map, not a substitute:

1. `json-api-client/docs/generated-client.md` — the generated surface, with
   `docs/server-prerequisites.md` and `docs/json-api-ts-consistency.md` beside it.
   `CONTEXT.md` is the glossary.
2. `json-api-client/docs/adr/` — rationale for the hard-to-reverse decisions, each
   verified against PHPStan level 9. Several rule out designs that look obviously
   correct; do not re-derive them.
3. Each repo's `CLAUDE.md` — conventions, gates, and a PHPStan footgun list where
   every entry cost real time.
4. The `php-client-delivery-plan` memory — phased build plan, resequenced.

## The five repos

| repo | role | state |
|---|---|---|
| `json-api` | core server + OpenAPI projector | v1.0.0 released; **15 unreleased commits**, release PR #155 open at 1.1.0 |
| `json-api-laravel` | Eloquent adapter | v1.0.0; PRs #50, #51 open and **blocked** |
| `json-api-symfony` | Doctrine adapter | v1.0.0; PR #139 open and **blocked** |
| `json-api-client` | PHP client runtime + bridges | scaffolded, Phase 1 + 1.5 merged |
| `json-api-client-codegen` | dev-only CLI | scaffolded, Phase 1A merged |
| `json-api-ts` | the other client | v1.0.0; 11 consistency items logged, none actioned |

All on HTTPS remotes, `gh` authenticated. **1Password is locked — commit with
`git commit --no-gpg-sign`.** This is authorised.

## Where the work stands

### Core 1.1.0 — complete, awaiting review

Fifteen commits since v1.0.0 (#154–#170). Every server prerequisite the PHP client
needed is in. Highlights, in dependency order rather than merge order:

- **#159 generator contract** — `info.x-generator.contract`, a monotonic integer,
  **release-scoped** (at most one bump per release cycle), enforced by
  `bin/contract-guard.php` against the last release tag. Currently **2**. An absent
  `x-generator` means contract 1, which is what v1.0.0 documents are.
- **#162 / #168 / #170 error catalogue** — 53 codes as named `<Code>Error` variants
  with `code`/`status` consts and a narrowed `source`, offered from an **open**
  `anyOf` so an unprojected code still validates. Assembled by **discovery**, not a
  roster, and contributable by an integration through `ContributesErrorCodes`.
  Responses extracted into nine shared `components.responses` and narrowed per
  status: documents shrank ~20–22%.
- **#163 the one real behaviour change** — a relation exposing a related endpoint to
  a type its server does not register is now **rejected at projection time**. Such a
  configuration previously returned 500 and rendered linkage with no `data` member.
  Maintainer's call: a correctness fix, not a breaking change, because nothing
  working could have depended on it.
- **#166 / #169 filter value schemas** — derived from the target field where
  resolvable, floored at `{type: string}` otherwise. **No projected filter carries an
  empty value schema any more.**
- **#160 temporal formats** — `format: date-time`/`date`/`time` emitted only when the
  configured format round-trips RFC 3339. Found that `Time`'s default `H:i:s` had
  never been valid.
- **#161 write `include`/`fields`** — declared on the 21 operations whose success
  response carries a resource document. Relationship mutations and `202` job
  responses correctly excluded.
- **#158 `ProjectedTypes`** — both artefacts derive their type set from one rule.

### What is blocking the release

**Nothing technical. It is waiting on maintainer review.** Greg reviews every
release by hand and **auto-merge must never be armed on a release PR** — see the
`never-auto-merge-releases` memory.

Note release PR #155 **cannot get CI**: GitHub does not trigger workflows on
`GITHUB_TOKEN`-authored PRs, and the repo ruleset requires eight status checks. It
therefore shows `BLOCKED`. The PR contains only `CHANGELOG.md` and
`.release-please-manifest.json`; every code commit it releases passed the full matrix
on its own PR. Resolving that is a maintainer decision — admin-merge, a PAT for the
release workflow, or a ruleset exemption.

### Adapters — done but blocked, and stale

`json-api-symfony#139` and `json-api-laravel#50` carry, for this release:

- the demo fixes for #163 (5 types, 7 relations, 2 servers)
- `JsonSchemaFactory` keyed off `ProjectedTypes`
- the `ServableResourceWarmer` guard
- demo filter constraints, and the doc corrections #169 made necessary
- the error-catalogue seam wiring (Symfony compiler pass, Laravel `DiscoveryScanner`)

**Two things to do before they can merge:**

1. **They are behind their own `main`s** — laravel by 1 commit, symfony by 2. Rebase
   before anything else; an agent already reported "fixing" two things that were
   already fixed upstream, which is the signature of a stale branch.
2. **They need core 1.1.0 published.** Every red check traces to
   `Class "haddowg\JsonApi\OpenApi\ProjectedTypes" not found` — the adapters install
   core from Packagist at `^1.0`. After the release: bump both to `^1.1`, **merge
   symfony first and let it land**, then laravel. Laravel's byte-compat job clones the
   Symfony bundle at its *default branch*, so racing them produces a spurious failure.

`json-api-laravel#51` (describedby expose-gate test) is independent and can merge
once `main` is green.

### PHP client — Phase 1 and the 1.5 gate are in

**Merged.** `json-api-client`: runtime primitives with no dependency on generated
code — PSR-18 transport, `ClientOptions` with a per-request header provider,
`Document`/`JsonApi` VOs, four `Page` kinds, `ResourceCollection` /
`PaginatedCollection`, the exception hierarchy with `byPath()` pointer remapping,
`Missing`, `Identifier`, `Conditionable`, projection/query bases. 211 tests.
`json-api-client-codegen`: the typed spec reader, descriptor model, and the
**golden-file mechanism** every emitter will be measured against. 90 tests.

**Phase 1.5 (the gate) is merged and its findings are the most valuable artefact
here.** It hand-wrote the complete generated output for `albums` — 27 files, 2,931
lines — and found four design errors that would otherwise have been discovered six
times over in the fan-out. All four are already corrected in the docs and ADRs; the one to
internalise is that **`??` silently destroys the `Missing` distinction**, so every
write-input member must be read with `array_key_exists`.

### What comes next for the client

**Phase 2 was deliberately deferred until the server prerequisites landed.** They now
have. The sequence:

1. **Regenerate the client fixture** from the final 1.1.0 document. It must be
   exported from **`json-api-laravel`'s workbench**, not core, and the demo providers
   register in `testbench.docker.yaml` not `testbench.yaml` — a plain export silently
   yields a three-type slice. The recipe is in
   `json-api-client-codegen/tests/fixtures/README.md`.
2. **Re-run the Phase 1.5 artefact against it.** The contract has changed
   substantially: admin grew from 2 types to 7, errors carry 53 catalogued codes
   behind narrowed shared responses, 21 write operations gained `include`/`fields`,
   filters are typed. Expect the hand-written output to need real revision.
3. **Phase 2 emitters**, fanned out by artefact family with worktree isolation.
   **Re-scope first** — the original estimate of ~114 classes for 13 types was wrong.
   Reality is 17 artefact kinds per type; one type with 2 relations and 3 actions
   produced 27 files, and the fixture is now 16 types. Per-relation query builders,
   per-relation handles, and the shapes/trait/write-helper classes were all omitted
   from that estimate.
4. Phases 3–5 (runtime behaviour needing generated code, bridges, docs/example) are
   unchanged in the plan.

## Decisions a new agent must not re-litigate

These were each settled with evidence, several after being wrong once:

- **Narrowing covers `include`, not sparse fieldsets.** Attribute markers cost 6× for
  a weaker guarantee and yield nothing for programmatic fieldsets (ADR 0001).
- **Reserved members take a leading `_`.** JSON:API guarantees a member name cannot
  begin with one (ADR 0002) — the PHP analogue of the TS client's `$`.
- **Three write input forms**, because no single form catches everything; the
  measured gap table is in ADR 0003.
- **The projection builders are `final` and share a trait**, not an inheritance
  chain. `AlbumQuery extends AlbumProjection` is rejected at level 9. This was
  corrected twice — see ADR 0014 before changing it.
- **Generate exactly what the contract permits.** An absent capability is an absent
  method, not a runtime guard. This governs nine surfaces, and it is why **codegen
  correctness is client correctness**.
- **The error catalogue is open.** An unlisted code must fall back, never throw.
- **Per-operation error narrowing was investigated and rejected** — 27 of 53 codes
  are not narrowable, four config predicates are *inverted*, and the three hosts gate
  body validation differently while sharing one projector. Per-status is the stopping
  point.

## Open, not blocking

- **Dependabot configs.** No repo has one, and `composer.lock` is gitignored
  everywhere (a deliberate no-lock library convention the lowest/highest matrix is
  built around). So a dev-tool bump lands silently inside whoever's PR runs next —
  PHPStan 2.2.14 did exactly that today and reddened an unrelated PR. Greg chose
  dependabot; it is not yet written.
- **Eleven `json-api-ts` consistency items**, now in `docs/json-api-ts-consistency.md`. Two are urgent: its
  codegen **cannot detect pagination against a v1 server** (it matches
  `page[number]`/`page[size]`; the server emits one `page` parameter with
  `style: deepObject`), and `$page` never exposes `total`. Typed error handling is
  now unblocked. Two latent readers would break the same way if 2xx responses or
  query parameters ever move into `components.*`.
- **Test-isolation findings in `json-api-laravel`**, reported but not actioned:
  `TestingKitSchemaConformanceTest` validates a different response body locally than
  on CI (ambient `app.debug` adds `links.describedby`), and the base
  `tests/Feature/TestCase.php` pins no config, so ~30 subclasses inherit it.
- **A core edge left alone deliberately:** a relation with `relatedTypes() === []`
  that exposes its related endpoint still produces a dangling `$ref`; the #163 guard
  iterates `relatedTypes()` and sees nothing.
- **`FullTextSearch`-style follow-ups** in the demos are done; `WhereThrough` on
  `artist.name` stays unconstrained on purpose — every constraint that would buy a
  type asserts something narrower than "a string".

## Working conventions that matter here

- **Never auto-merge a release PR.** Greg reviews every one.
- Conventional Commit PR titles — squash-merge drives release-please.
- PR bodies read as external-contributor prose, no internal planning references.
- Gates everywhere: `composer test`, `phpstan` (level 9), `cs-check`, across PHP
  8.3/8.4/8.5 × lowest/highest.
- **Run PHPStan against the version CI resolves**, not the pinned local one. Three
  latent failures surfaced that way in one day.
- Core is read-only from an adapter task and vice versa; report cross-repo needs
  rather than reaching.
- Byte-compat (`bin/byte-compat.php` from `json-api-laravel`) must show all four
  artefacts identical. It needs both siblings on their PR branches while #139/#50 are
  unmerged.
- Symfony worktree runs need an isolated `TMPDIR` — functional kernels cache into
  `sys_get_temp_dir()` and collide across checkouts.
