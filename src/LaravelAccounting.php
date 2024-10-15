<?php

namespace RingleSoft\LaravelAccounting;

use Illuminate\Support\Collection;
use RingleSoft\LaravelAccounting\Models\Account;

class LaravelAccounting
{
    /**
     * @return Collection
     */
    public function chartsOfAccounts(): Collection
    {
        return Account::all();
    }}
