<?php

namespace RingleSoft\LaravelAccounting\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \RingleSoft\LaravelAccounting\LaravelAccounting
 */
class LaravelAccounting extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \RingleSoft\LaravelAccounting\LaravelAccounting::class;
    }
}
