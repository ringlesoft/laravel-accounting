<?php

namespace RingleSoft\LaravelAccounting\DTOs;

use Closure;
use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;

class TaxData
{
    public function __construct(
        public AccountPresetContract $account,
        public float $rate,
        public ?Closure $amount = null,
        public ?AccountPresetContract $baseAccount = null,
        public ?string $memo = null
    ) {}
}
