---
status: accepted
---

# The framework bridges ship in the runtime, and the codegen is its own package

The Laravel and Symfony bridges belong in this package, under `Bridge\Laravel\` and `Bridge\Symfony\`, with their framework dependencies in `require-dev` and `suggest`, because each is a service provider, a config file and a console command, too small to earn a repository, and cross-repository lockstep between server adapters has been a steady source of false CI failures. The code generator is the opposite case: it is a separate, dev-only package (`haddowg/json-api-client-codegen`) so the spec parser and emitter never reach a production autoload map, mirroring the TypeScript split between client and codegen.
