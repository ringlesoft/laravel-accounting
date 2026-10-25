<?php

namespace RingleSoft\LaravelAccounting\Support;

use Closure;

/**
 * The chart-structure rules enforced on accounts (see the
 * `enforce_chart_structure` config): every account has a parent, the
 * package chart's root accounts can't be changed or deleted, and explicit
 * codes must fit the code scheme under their parent.
 */
class ChartStructure
{
    protected static int $bypassDepth = 0;

    /**
     * Whether the rules are switched on in config.
     */
    public static function enabled(): bool
    {
        return (bool) config('laravel_accounting.enforce_chart_structure', true);
    }

    /**
     * Whether the rules apply right now: switched on, and not bypassed.
     */
    public static function enforced(): bool
    {
        return static::enabled() && static::$bypassDepth === 0;
    }

    /**
     * Run $callback with the rules suspended. Reserved for the package's own
     * chart seeding, which is what creates the root accounts.
     *
     * @internal
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function bypass(Closure $callback): mixed
    {
        static::$bypassDepth++;

        try {
            return $callback();
        } finally {
            static::$bypassDepth--;
        }
    }
}
