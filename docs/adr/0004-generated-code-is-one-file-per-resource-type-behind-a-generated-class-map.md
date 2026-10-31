---
status: accepted
---

# Generated code is one file per resource type, behind a generated class map

The generated surface is about 170 classes for a mid-sized API, and none can go without losing a guarantee, so we reduce the files instead: each resource type's classes share one `resource-<type>.php`, the enums and root client get a file each, and a generated `autoload.php` maps every class to its file for the consumer to register once through `autoload.files`. The output is committed to the consuming repository, so it is reviewed, diffed and versioned like any other code.

## Considered options

- **One class per file under PSR-4** needs no autoload setup, but buys no guarantee the grouped form lacks and spreads a one-type regeneration across a dozen files.
- **Composer `classmap` autoloading** works equally well, but every regeneration then needs a `dump-autoload`, and a forgotten one fails only for newly added classes. The generated map is rewritten with the code, so it cannot go stale.
- **One file for the whole API** degrades review, merge conflicts and PHPStan's per-file cache all at once.
- **Generating at install time** into an ignored directory trades committed code for a committed spec and lets someone else's API break your production build.

## Consequences

File names carry a hyphen, which no class name can, so a file never shadows a class on a case-insensitive filesystem. The directory is deliberately not PSR-4, so PHP-CS-Fixer's `psr_autoloading` rule must exclude it.
