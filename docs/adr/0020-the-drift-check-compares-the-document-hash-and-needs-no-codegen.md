---
status: accepted
---

# The drift check compares the document hash and needs no codegen

`json-api-client:check` fetches the server's OpenAPI document and compares its sha256 with the one the generated `autoload.php` records, instead of regenerating and diffing. The codegen is a dev dependency, and the check is most useful where it is absent: in a deploy pipeline, against the server the release will actually talk to. It cannot see a hand edit to the generated code or a codegen upgrade that would change the output; the codegen's own `--check`, run in development CI, covers both.
