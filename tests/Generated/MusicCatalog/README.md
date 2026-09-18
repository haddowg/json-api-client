# The `albums` reference artefact

Hand-written output for one resource type, exactly as a correct emitter must produce it. Six
emitters are being built in parallel; this is the thing they target, so that a six-way
coordination problem becomes a spec.

It is not library code. It lives under `tests/` in the `…\Tests\Generated\MusicCatalog`
namespace, it is analysed by PHPStan at level 9 and checked by PHP-CS-Fixer alongside `src/`,
and it is exercised end to end by `MusicCatalogUsageTest`. Nothing in it is autoloaded by the
package.

Source: `json-api-client-codegen/tests/fixtures/music-catalog.openapi.json`, the `albums` type
and everything it references.

## What is here

| File | Kind | Emitted per |
|---|---|---|
| `AlbumStatus.php` | enum from `x-enum-varnames` | enumerated schema |
| `AlbumHasArtist.php`, `AlbumHasTracks.php` | relation marker | relation |
| `AlbumBase.php` | resource base: attributes, linkage companions, `_rel()` | type |
| `Album.php` | concrete DTO, relation accessors, static write factories | type |
| `AlbumShapes.php` | `@phpstan-type` aliases and descriptor constants | type |
| `AlbumProjecting.php` | the shared `withX()` trait | type |
| `AlbumProjection.php` | write-response projection | type |
| `AlbumQuery.php` | read query builder and terminal reads | collection operation |
| `AlbumCreate.php`, `AlbumUpdate.php` | named-argument write DTOs | writable type |
| `AlbumCreateBuilder.php`, `AlbumUpdateBuilder.php` | fluent write builders | writable type |
| `AlbumWrite.php` | shared write coercions | writable type |
| `Albums.php` | type accessor | type |
| `AlbumHandle.php` | id handle | type |
| `AlbumArtistRelation.php`, `AlbumTracksRelation.php` | relationship handle | relation |
| `AlbumActions.php`, `AlbumCollectionActions.php` | custom actions | action scope |
| `MusicCatalogClient.php` | root client | document |

`Artist`, `ArtistBase`, `ArtistHasAlbums`, `Track`, `TrackBase` and `TrackHasAlbum` are
**trimmed companions**: only what the `albums` surface reaches, so the relation accessors have
real return types. A full run emits the same 17 artefact kinds for them too. Track's `playlists`
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
`#[RequiresPhp('>= 8.4.0')]` — the 8.3 leg skips it and never autoloads the artefact, while
PHPStan and PHP-CS-Fixer (both on 8.4 in CI) analyse it in full. A contributor running PHPStan
locally on 8.3 will hit a parse error here; run it on 8.4, as CI does.

## Proofs

`MusicCatalogUsageTest` runs the surface against a recording PSR-18 client: 20 tests, 100
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
