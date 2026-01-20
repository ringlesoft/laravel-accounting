<?php

namespace RingleSoft\LaravelAccounting\DTOs;

class VoucherDataItem
{
    public function __construct(
        public string $accountId,
        public float $amount,
        public ?string $memo = null
    ) {}

}
