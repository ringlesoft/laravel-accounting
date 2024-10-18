<?php

namespace RingleSoft\LaravelAccounting\Models;

use Illuminate\Database\Eloquent\Model;
use RingleSoft\LaravelAccounting\Traits\HasTablePrefix;
use RingleSoft\LaravelAccounting\Traits\MultiTenant;

class Account extends Model
{
    use HasTablePrefix, MultiTenant;

    public function __construct()
    {
        $this->table = config('laravel_accounting.table_prefix', 'la').'_accounts';
        parent::__construct();
    }
}
