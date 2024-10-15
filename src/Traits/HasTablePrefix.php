<?php

namespace RingleSoft\LaravelAccounting\Traits;

use Illuminate\Support\Facades\Config;

trait HasTablePrefix
{
    public function getTable(): string
    {
        $prefix = trim(Config::get('accounting.table_prefix', 'la'));
        $tableName = parent::getTable();
        return str_starts_with($tableName, $prefix) ? $tableName : "{$prefix}_$tableName";
    }
}
