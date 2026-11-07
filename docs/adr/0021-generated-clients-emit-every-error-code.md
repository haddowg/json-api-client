---
status: accepted
---

# Generated clients emit every error code

Supersedes ADR 0005. The server now declares a typed `meta` per code, which a class shipped here could not carry without the runtime knowing every server's catalogue, and skipping the codes the installed runtime shipped tied what a client contained to the runtime version beside the codegen. So the codegen emits a class for every code a document catalogues, core's included, each with a typed `meta()` where the code declares one, and the runtime dispatches only through the generated map, falling back to the status class for a code it does not list.
