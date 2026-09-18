<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Support;

/**
 * Conditional composition for query builders, where the callback's narrowing is **erased**.
 *
 * The difference from {@see Conditionable} is load-bearing rather than stylistic. A query
 * builder is immutable and narrows its projection as it goes, so the callback's return has to
 * be honoured at runtime or the branch would do nothing at all. What it must *not* do is carry
 * the narrowing out: a runtime condition cannot produce a compile-time projection, and
 * `when($flag, fn ($q) => $q->withArtist())` typed as narrowed would claim a relation that is
 * only sometimes there.
 *
 * So the result is typed `static` — the projection the builder already had.
 *
 * The honest consequence: a conditionally-included relation stays un-narrowed. `$album->artist`
 * is then a static error, and the relation is reached through `artistRef()`/`hasArtist()` or
 * after a runtime check — the same lane as `with(...)` and a deep include.
 */
trait QueryConditionable
{
    /**
     * Run `$callback` when `$condition` is truthy, otherwise `$default`.
     *
     * A `Closure` condition is resolved against the builder first. Any other value is used for
     * its truthiness — a callable *string* is a value, not a condition to invoke.
     *
     * @param mixed                          $condition
     * @param callable(static): mixed        $callback
     * @param (callable(static): mixed)|null $default
     *
     * @return static
     */
    public function when(mixed $condition, callable $callback, ?callable $default = null): static
    {
        if ($this->conditionHolds($condition)) {
            return $this->sameProjection($callback($this));
        }

        if ($default !== null) {
            return $this->sameProjection($default($this));
        }

        return $this;
    }

    /**
     * The inverse of {@see self::when()}: run `$callback` when `$condition` is falsy.
     *
     * @param mixed                          $condition
     * @param callable(static): mixed        $callback
     * @param (callable(static): mixed)|null $default
     *
     * @return static
     */
    public function unless(mixed $condition, callable $callback, ?callable $default = null): static
    {
        return $this->when(!$this->conditionHolds($condition), $callback, $default);
    }

    /**
     * Hand the builder to `$callback` for a side effect and carry on with the builder itself.
     *
     * @param callable(static): mixed $callback
     *
     * @return static
     */
    public function tap(callable $callback): static
    {
        $callback($this);

        return $this;
    }

    /**
     * Keep the builder a callback handed back, at the projection it already had.
     *
     * @return static
     */
    private function sameProjection(mixed $result): static
    {
        return $result instanceof static ? $result : $this;
    }

    private function conditionHolds(mixed $condition): bool
    {
        return (bool) ($condition instanceof \Closure ? $condition($this) : $condition);
    }
}
