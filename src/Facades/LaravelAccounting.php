<?php

namespace RingleSoft\LaravelAccounting\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \RingleSoft\LaravelAccounting\LaravelAccounting
 *
 * @mixin \RingleSoft\LaravelAccounting\LaravelAccounting
 */
class LaravelAccounting extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RingleSoft\LaravelAccounting\LaravelAccounting::class;
    }
}
