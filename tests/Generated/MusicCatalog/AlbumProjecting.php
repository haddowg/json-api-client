<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Tests\Generated\MusicCatalog;

use haddowg\JsonApiClient\Exceptions\UnknownFieldException;
use haddowg\JsonApiClient\Query\Projection;

/**
 * The projection surface of `albums`: what a read brings back, shared by the write projection
 * and the query builder.
 *
 * A trait rather than a shared base class, and the reason is a level-9 result rather than
 * taste. `@return static<TProj&AlbumHasArtist>` over a `static`-returning primitive resolves
 * cleanly in a **final** class and is rejected in a non-final one:
 *
 * ```
 * Method …::withArtist() should return static(…<AlbumHasArtist&TProj of object>)
 * but returns static(…<TProj of object>).
 * ```
 *
 * So `AlbumQuery extends AlbumProjection` would put the narrowing methods on an extendable
 * class and fail. A trait used by two final classes is analysed once per using class, both of
 * them final, and passes for both — with no duplicated method bodies, no suppression
 * comment, and no inline `@var`.
 *
 * @template TProj of object
 *
 * @phpstan-require-extends Projection<TProj>
 *
 * @generated from the `include` and `fields` parameters of the `albums` read operations
 */
trait AlbumProjecting
{
    /**
     * Include the `artist` relation, narrowing the projection so `$album->artist` type-checks.
     *
     * @return static<TProj&AlbumHasArtist>
     */
    public function withArtist(): static
    {
        return $this->including('artist');
    }

    /**
     * Include the `tracks` relation, narrowing the projection so `$album->tracks` type-checks.
     *
     * @return static<TProj&AlbumHasTracks>
     */
    public function withTracks(): static
    {
        return $this->including('tracks');
    }

    /**
     * Sparse fieldsets, with the primary type's tokens checked against the descriptor.
     *
     * Overriding the runtime method rather than adding a typed twin keeps one name for one
     * concept. The signature is unchanged — narrowing an inherited parameter to an array shape
     * would be a contravariance error — so the check is a runtime one, which is the right place
     * for it anyway: a fieldset assembled from a request could never satisfy a literal union.
     *
     * Only `fields[albums]` is checked here. A full run knows every type's members and checks
     * all of them.
     *
     * @param array<string, list<string>> $fields
     *
     * @return static
     *
     * @throws UnknownFieldException when `fields[albums]` names a member `albums` does not have
     */
    public function fields(array $fields): static
    {
        foreach ($fields[AlbumBase::TYPE] ?? [] as $field) {
            if (!\in_array($field, AlbumShapes::FIELDS, true)) {
                throw UnknownFieldException::for(AlbumBase::TYPE, $field, AlbumShapes::FIELDS);
            }
        }

        return parent::fields($fields);
    }
}
