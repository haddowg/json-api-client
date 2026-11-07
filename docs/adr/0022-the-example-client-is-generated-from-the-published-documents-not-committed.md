# The example's client is generated from the published documents, not committed

`examples/music-catalog` used to commit both a copy of the bundle's music-catalog document and the client generated from it, so each refresh was a hand-copied document and a large diff nobody could usefully review. Now package.json pins `@haddowg/json-api-symfony-specs` exactly, `composer generate-example` generates the client before the tests and PHPStan, and CI reviews a change to it as a labelled diff report, generated on the pull request's base and head, instead of a committed diff. The codegen is installed in its own Composer project, `tools/codegen`, pinned to a commit until it releases, not as a require-dev here. This package's tests cover a project without the codegen installed, and keeping it apart also keeps the codegen's own dependency on this runtime out of this runtime's dependency graph.

## Considered Options

- **A require-dev on the codegen through a VCS repository.** This is the simplest wiring, but installing the codegen changes what the bridges' tests see, so the tests that check the "install the codegen" message would fail.
- **Checking the codegen out in CI.** This works in CI only, so a local `composer test` would need a sibling checkout at the right commit.
- **A lockfile for the specs package.** The package has no dependencies and is pinned exactly, so a lock would add only an integrity hash. `.npmrc` turns the lock off, and `.github/scripts/check-specs-pin` checks that the pin is exact, that the version is published, and that it is not a prerelease on `main` or in a release.

## Consequences

A pull request may pin a bundle pull request's prerelease, `0.0.0-pr.<n>.<sha>`, while it waits for the bundle to release, but the bundle deletes that prerelease when its pull request closes. Pin a release before merging.
