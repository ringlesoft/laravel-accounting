<?php

namespace RingleSoft\LaravelAccounting\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    public function __construct()
    {
        $this->table = config('accounting.table_prefix', 'la').'_accounts';
        parent::__construct();
    }
}
