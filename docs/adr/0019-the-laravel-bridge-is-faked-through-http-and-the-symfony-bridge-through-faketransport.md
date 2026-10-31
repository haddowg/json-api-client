---
status: accepted
---

# The Laravel bridge is faked through `Http`, the Symfony bridge through `FakeTransport`

The Laravel bridge's transport is the PSR-18 client Laravel's own `Http` factory builds, so `Http::fake()` answers a generated client's requests and `Http::assertSent()` sees them, as it does for every other outbound call an application makes. A second fake with its own assertions would be one more thing to learn for no gain. Symfony has no equivalent recorder (`MockHttpClient` answers but does not assert), so its bridge swaps in the package's `FakeTransport` under `fake: true`, which also serves a standalone client.
