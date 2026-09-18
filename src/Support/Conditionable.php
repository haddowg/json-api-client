<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Support;

/**
 * Conditional composition for write builders: `when()`, `unless()` and `tap()`.
 *
 * The write-builder flavour, and the difference from the query-builder one is load-bearing
 * rather than stylistic. A write builder narrows nothing, so the callback's return is
 * honoured — `callable(static): (static|null)`, where returning `null` keeps the receiver.
 * That lets a callback either mutate in place or hand back a replacement.
 *
 * Query builders get their own flavour, where the return is discarded because a runtime
 * condition must not produce a compile-time projection.
 */
trait Conditionable
{
    /**
     * Run `$callback` when `$condition` is truthy, otherwise `$default`.
     *
     * A `Closure` condition is resolved against the builder first. Any other value is used
     * for its truthiness — a callable *string* is a value, not a condition to invoke.
     *
     * @param mixed                                  $condition
     * @param callable(static): (static|null)        $callback
     * @param (callable(static): (static|null))|null $default
     *
     * @return static
     */
    public function when(mixed $condition, callable $callback, ?callable $default = null): static
    {
        if ($this->conditionHolds($condition)) {
            return $callback($this) ?? $this;
        }

        if ($default !== null) {
            return $default($this) ?? $this;
        }

        return $this;
    }

    /**
     * The inverse of {@see self::when()}: run `$callback` when `$condition` is falsy.
     *
     * @param mixed                                  $condition
     * @param callable(static): (static|null)        $callback
     * @param (callable(static): (static|null))|null $default
     *
     * @return static
     */
    public function unless(mixed $condition, callable $callback, ?callable $default = null): static
    {
        return $this->when(!$this->conditionHolds($condition), $callback, $default);
    }

    /**
     * Hand the builder to `$callback` and return the builder whatever the callback returns.
     *
     * The escape hatch for a side effect mid-chain (logging, an assertion) where honouring
     * a return would be wrong.
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

    private function conditionHolds(mixed $condition): bool
    {
        return (bool) ($condition instanceof \Closure ? $condition($this) : $condition);
    }
}
