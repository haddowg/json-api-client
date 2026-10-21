# The `albums` reference artefact

Hand-written output for one resource type, exactly as a correct emitter must produce it. Six
emitters are being built in parallel; this is the thing they target, so that a six-way
coordination problem becomes a spec.

It is not library code. It lives under `tests/` in the `…\Tests\Generated\MusicCatalog`
namespace, it is analysed by PHPStan at level 9 and checked by PHP-CS-Fixer alongside `src/`,
and it is exercised end to end by `MusicCatalogUsageTest`. Nothing in it is autoloaded by the
package; the test suite loads it through `autoload.php`, registered in `autoload-dev.files`.

Source: `json-api-client-codegen/tests/fixtures/music-catalog.openapi.json`, the `albums` type
and everything it references.

## What is here

Generated output is grouped one file per resource type rather than one class per file, and
`autoload.php` maps every class to its file. The files are named `resource-<type>.php` because
a hyphen cannot appear in a class name: no file can collide with a class on a case-insensitive
filesystem (`albums.php` and the `Albums` accessor would be the same file on macOS), and no
PSR-4 lookup can resolve one by accident. Regenerating rewrites the map, so a new type never
needs a `composer dump-autoload`. Classes within a file are ordered interfaces, traits, enums,
abstract bases, then final classes, so every declaration's parent is already declared.

| File | Holds |
|---|---|
| `resource-albums.php` | everything the `albums` type generates, listed below |
| `resource-artists.php`, `resource-tracks.php` | trimmed companions (see below) |
| `enums.php` | every enum the document declares |
| `client.php` | the root client |
| `autoload.php` | the class-to-file map |

The kinds `resource-albums.php` holds:

| Class | Kind | Emitted per |
|---|---|---|
| `AlbumHasArtist`, `AlbumHasTracks` | relation marker | relation |
| `AlbumProjecting` | the shared `withX()` trait | type |
| `AlbumBase` | resource base: attributes, linkage companions, `_rel()` | type |
| `Album` | concrete DTO, relation accessors, static write factories, `@phpstan-type` aliases and descriptor constants | type |
| `AlbumProjection` | write-response projection | type |
| `AlbumQuery` | read query builder and terminal reads | collection operation |
| `AlbumCreate` | named-argument create DTO | writable type |
| `AlbumCreateBuilder` | fluent create builder | writable type |
| `AlbumUpdate` | named-argument update DTO, and its own fluent form | writable type |
| `Albums` | type accessor | type |
| `AlbumHandle` | id handle | type |
| `AlbumArtistRelation`, `AlbumTracksRelation` | relationship handle | relation |
| `AlbumActions`, `AlbumCollectionActions` | custom actions | action scope |

Plus `AlbumStatus` in `enums.php`. That is 14 artefact kinds. Three earlier ones are gone
without losing anything: the write coercions are the runtime's `Coerce`, the shape aliases and
constants live on `Album`, and an update needs no separate builder because it has no required
member. `AlbumUpdate`'s setters each return a new input and are `@phpstan-pure`, which is what
turns a discarded setter call into a static error (see the proofs below).

`Artist`, `ArtistBase`, `ArtistHasAlbums`, `Track`, `TrackBase` and `TrackHasAlbum` are
**trimmed companions**: only what the `albums` surface reaches, so the relation accessors have
real return types. A full run emits the same 14 artefact kinds for them too. Track's `playlists`
relation keeps its linkage companions and has no hydrated accessor, because that would pull in
the `playlists` type.

## The 8.4 target, and what 8.3 drops

This is the 8.4 form, which is the superset. Method accessors are the canonical contract and
carry all the behaviour; on an 8.4 target the emitter *additionally* writes get-only property
hooks that delegate to them.

An 8.3 target drops exactly three things and nothing else:

1. `public string $title { get => $this->title(); }` on `AlbumBase`, `ArtistBase`, `TrackBase`
   and the two concrete classes — one hook per attribute, per `id`, per `type`, and per relation.
2. `public ?Artist $artist { get; }` on every relation marker interface.
3. With them, the property forms at the call site: `$album->title` and `$album->artist->name`
   become `$album->title()` and `$album->artist()->name()`.

A hook is a one-line delegation and never carries behaviour, which is what makes drift between
the two forms structurally impossible. Both are live simultaneously on 8.4.

**This has a CI consequence worth knowing about.** Property-hook syntax is a parse error on PHP
8.3, and this package's test matrix still runs 8.3. So `MusicCatalogUsageTest` carries
`#[RequiresPhp('>= 8.4.0')]` — the 8.3 leg skips it and the map never loads the artefact, while
PHPStan and PHP-CS-Fixer (both on 8.4 in CI) analyse it in full. A contributor running PHPStan
locally on 8.3 will hit a parse error here; run it on 8.4, as CI does.

## Proofs

`MusicCatalogUsageTest` runs the surface against a recording PSR-18 client: 21 tests, 102
assertions, every negative case an assertion rather than a comment.

`MusicCatalogTypes` asserts the *static* types with `PHPStan\Testing\assertType()`. It is
analysed and never executed — the function does not exist at runtime. A mismatch is a
non-ignorable `phpstan.type` error.

The cases that must **fail** to compile cannot live in a tree that has to stay green, so each
was confirmed by writing it, running PHPStan at level 9, and removing it. The exact messages:

| Expression | Level 9 says |
|---|---|
| `$narrowedToArtist->tracks()` | `Call to an undefined method AlbumBase&AlbumHasArtist::tracks(). [method.notFound]` |
| `$narrowedToArtist->tracks` | `Access to an undefined property AlbumBase&AlbumHasArtist::$tracks. [property.notFound]` |
| `$unnarrowed->artist()` | `Call to an undefined method AlbumBase::artist(). [method.notFound]` |
| `query()->when($f, fn ($q) => $q->withArtist())->get()[0]->artist` | `Access to an undefined property AlbumBase::$artist. [property.notFound]` |
| `query()->with('artist')->get()[0]->artist` | `Access to an undefined property AlbumBase::$artist. [property.notFound]` |
| `$album->title = 'nope'` | `Property AlbumBase::$title is not writable. [assign.propertyReadOnly]` |
| `sort('+releasedAt')` | `Parameter #1 …$tokens … expects '-releasedAt'\|'-status'\|'-title'\|'releasedAt'\|'status'\|'title', '+releasedAt' given. [argument.type]` |
| `sort('artwork')` | same, `'artwork' given` — a real field that is not sortable |
| `sort('title', 'artist')` | `Parameter #2 …` — the offending position is named |
| `withCount(['_self_'])` | `Parameter #1 $tokens … expects list<'tracks'>, array{'_self_'} given. [argument.type]` |
| `page(0)` | `Parameter #1 $number … expects int<1, max>, 0 given. [argument.type]` |
| `whereRating(['min' => 'four'])` | `… expects array{min?: float, max?: float}, array{min: 'four'} given. [argument.type]` |
| `filter(['releasedAt' => ['min' => 5]])` | `… expects array{…releasedAt?: array{min?: DateTimeImmutable\|string, …}…}, array{releasedAt: array{min: 5}} given. [argument.type]` |
| `Album::projection()->sort('title')` | `Call to an undefined method AlbumProjection<AlbumBase>::sort(). [method.notFound]` |
| `Album::projection()->page(1)` | `Call to an undefined method AlbumProjection<AlbumBase>::page(). [method.notFound]` |
| `id('1')->artist()->add([…])` | `Call to an undefined method AlbumArtistRelation::add(). [method.notFound]` |
| `$albums->_page()->after` | `Access to an undefined property PageNumber::$after. [property.notFound]` |
| `$album->_rel('artists')` | `Parameter #1 $name of method AlbumBase::_rel() expects 'artist'\|'tracks', 'artists' given. [argument.type]` |
| `new AlbumCreate(status: …)` | `Missing parameter $title (string) … [argument.missing]` |
| `new AlbumCreate(title: 'x', titel: 'y')` | `Unknown parameter $titel … [argument.unknown]` |
| `Album::create(['title' => 123])` | `Parameter #1 $input … expects array{title: string, …}, array{title: 123} given. [argument.type]` |
| `$update->title('x');` as a statement | `Call to method AlbumUpdate::title() on a separate line has no effect. [method.resultUnused]` |
| `$update->when(true, function (AlbumUpdate $u): void { … })` | `Parameter #2 $callback … expects callable(AlbumUpdate): AlbumUpdate, Closure(AlbumUpdate): void given. [argument.type]` |
| `whereTitle(123)` | `Parameter #1 $value … expects string, int given. [argument.type]` |

Two expressions produce **no** static error, and both are the documented blind spot rather than
a defect:

- `Album::create(['title' => 'x', 'averageRating' => 4.0])` — an unknown *optional* key in an
  array shape. Caught at runtime by `AlbumCreate::from()` with a did-you-mean.
- `filter(['rating' => ['minimum' => 4.0]])` — an unknown key one level down, inside a
  structured filter's own value. Caught at runtime by `AlbumQuery::guardFilters()`, which had to
  be extended to reach nested members; see the findings note in the PR.

## Decisions an emitter has to make that the artefact records

- **`withX()` lives in a trait, not a shared base class.** `@return static<TProj&Marker>` over a
  `static`-returning primitive resolves at level 9 in a **final** class and is rejected in a
  non-final one. `AlbumQuery extends AlbumProjection` would therefore fail. A generic trait used
  by two final classes is analysed once per using class and passes for both.
- **Exactly one type assertion per generated read class.** `AlbumQuery::hydrate()` is the only
  place the artefact asserts a type, and it is unavoidable: the runtime always builds an
  `Album`, `TProj` is a template parameter no bound can satisfy, and typing the result
  `Album&TProj` would collapse to plain `Album` and undo every narrowing.
  `ResourceContext::hydrate()` exists to erase the concrete class so the assertion is legal.
- **`array_key_exists`, never `??`, when reading a write input array.** `??` folds an explicit
  `null` into the sentinel that means absent, turning "clear this member" into "leave it alone".
  On an all-optional patch shape that is the difference between a working `PATCH` and a no-op.
- **One required-member guard per member in `build()`.** A collected `$missing` list reads
  better and does not narrow: PHPStan cannot connect `$missing !== []` back to the property, so
  the constructor call downstream fails.
- **Nested object attributes are guarded, not asserted.** `AlbumBase::releaseInfo()` rebuilds its
  declared shape member by member. The alternatives are losing the shape or asserting it; this is
  the only one that is both typed and honest about a server sending the wrong thing.
