<?php

namespace RingleSoft\LaravelAccounting\Contracts;

use Closure;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

interface PostableTemplate
{
    public function configure(): void;

    public function debit(AccountPreset $account, ?Closure $amount, Closure|string|null $memo = null): self;

    public function credit(AccountPreset $account, ?Closure $amount, Closure|string|null $memo = null): self;

    public function tax(AccountPreset $taxAccount, float $rate, ?Closure $amount, ?AccountPreset $baseAccount): self;

    public function make(PostableModel $model): VoucherData;
}
