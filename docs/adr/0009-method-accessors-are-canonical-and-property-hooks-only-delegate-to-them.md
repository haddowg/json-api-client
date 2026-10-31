---
status: accepted
---

# Method accessors are canonical, and property hooks only delegate to them

The runtime stays on PHP 8.3, but property access (`$album->title`) reads better on 8.4, and two independent implementations of every accessor would drift. Method accessors (`title()`, `artist()`) are always generated and carry all the behaviour; an 8.4 target, which defaults to the running PHP version, additionally emits get-only property hooks that are one-line delegations to those methods. Immutability likewise comes from hooks and method accessors, because `readonly` with selectively initialised attributes is rejected at level 9.
