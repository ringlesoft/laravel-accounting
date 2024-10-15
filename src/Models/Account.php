<?php

namespace RingleSoft\LaravelAccounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use RingleSoft\LaravelAccounting\Traits\HasTablePrefix;
use RingleSoft\LaravelAccounting\Traits\MultiTenant;

class Account extends Model
{
    use HasTablePrefix, MultiTenant;


    public function __construct()
    {
        $this->table = config('accounting.table_prefix', 'la').'_accounts';
        parent::__construct();
    }
}
