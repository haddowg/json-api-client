<?php

declare(strict_types=1);

namespace haddowg\JsonApiClient\Support;

/**
 * Conditional composition for immutable write inputs: `when()`, `unless()` and `tap()`.
 *
 * The flavour for a write input whose fluent setters return a new instance rather than
 * mutating the receiver. The callback must return the input it built: unlike
 * {@see Conditionable}, `null` is not accepted, because a callback that calls a setter and
 * returns nothing would drop the change silently. Requiring `static` turns that into a type
 * error.
 */
trait ImmutableConditionable
{
    /**
     * Run `$callback` when `$condition` is truthy, otherwise `$default`.
     *
     * A `Closure` condition is resolved against the input first. Any other value is used for
     * its truthiness.
     *
     * @param mixed                            $condition
     * @param callable(static): static         $callback
     * @param (callable(static): static)|null  $default
     *
     * @return static
     */
    public function when(mixed $condition, callable $callback, ?callable $default = null): static
    {
        if ($this->conditionHolds($condition)) {
            return $callback($this);
        }

        return $default !== null ? $default($this) : $this;
    }

    /**
     * The inverse of {@see self::when()}: run `$callback` when `$condition` is falsy.
     *
     * @param mixed                            $condition
     * @param callable(static): static         $callback
     * @param (callable(static): static)|null  $default
     *
     * @return static
     */
    public function unless(mixed $condition, callable $callback, ?callable $default = null): static
    {
        return $this->when(!$this->conditionHolds($condition), $callback, $default);
    }

    /**
     * Hand the input to `$callback` and return it unchanged, whatever the callback returns.
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
