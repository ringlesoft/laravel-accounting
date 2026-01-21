<?php

namespace RingleSoft\LaravelAccounting\DTOs;

use Closure;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class TaxData
{
    public function __construct(
        public AccountPreset $account,
        public float $rate,
        public ?Closure $amount = null,
        public ?AccountPreset $baseAccount = null,
        public ?string $memo = null
    ) {}

}
