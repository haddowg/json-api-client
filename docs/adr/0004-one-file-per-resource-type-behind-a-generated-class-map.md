---
status: accepted
---

# Generated code is one file per resource type, behind a generated class map

The generated surface is about 170 classes for the 1.1.0 music-catalog document's default
server, and every one of them is load-bearing: relation markers carry the `include` narrowing,
the query and projection builders must be `final` classes sharing a trait, and relation handles
expose only the verbs each relation permits. Since the classes cannot shrink further without
losing a guarantee, we reduce the files instead. Each resource type's classes go in one file
(`resource-albums.php`), enums and the root client get one file each, and a generated
`autoload.php` maps every class to its file. The user registers that map once through
composer.json's `autoload.files`. One class per file would have been about 170 files; this is
about 19.

## Considered options

**One class per file under PSR-4** was the original choice. It needs no autoload setup, but it
buys no guarantee the grouped form lacks, and a regeneration that changes one type spreads
across a dozen files.

**Composer's `classmap` autoloading** over the grouped files works as well as the generated map.
Both were verified at level 9: narrowing holds across files and the negative cases are still
reported. We rejected it because every regeneration then needs a `composer dump-autoload`, and
forgetting one fails only for *newly added* classes, long after the regeneration that caused it.
The generated map is rewritten with the code, so it cannot go stale.

**A single file for the whole API** was rejected earlier and stays rejected. Per-type files keep
review, merge conflicts and PHPStan's per-file cache scoped to the type that changed.

## Consequences

File names carry a hyphen (`resource-albums.php`) because no class name can. A bare
`albums.php` is the same file as the `Albums` accessor's on a case-insensitive filesystem, which
macOS and Windows both are, and a PSR-4 lookup for `Albums` would then load it.

Declarations inside a file are ordered interfaces, traits, enums, abstract bases, then final
classes, so every parent is declared before the child that names it.

The generated directory is deliberately not PSR-4, so a project that applies PHP-CS-Fixer's
`psr_autoloading` rule must exclude it, as it would exclude any generated code.
