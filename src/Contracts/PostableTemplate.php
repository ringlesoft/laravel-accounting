<?php

namespace RingleSoft\LaravelAccounting\Contracts;

use Closure;
use Illuminate\Support\Carbon;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

interface PostableTemplate
{
    /**
     * Configure the template (Add debit/credit entries)
     * @return void
     */
    public function configure(): void;

    /**
     * Add a debit entry to the template
     * @param AccountPreset $account
     * @param Closure(float): float|null $amount
     * @param Closure|string|null $memo
     * @return self
     */
    public function debit(AccountPreset $account, ?Closure $amount, Closure|string|null $memo = null): self;

    /**
     * Add a credit entry to the template
     * @param AccountPreset $account
     * @param Closure(float): float|null $amount
     * @param Closure|string|null $memo
     * @return self
     */
    public function credit(AccountPreset $account, ?Closure $amount, Closure|string|null $memo = null): self;

    /**
     * Set the description of the voucher
     * @param Closure(PostableModel $model): string|string $description
     * @return self
     */
    public function describe(Closure|string $description): self;

    /**
     * Set the date of the voucher
     * @param Closure(PostableModel $model): Carbon|Carbon $date
     * @return self
     */
    public function date(Closure|Carbon $date): self;

    /**
     * Generate a voucher data from the template
     * @param PostableModel $model
     * @return VoucherData
     */
    public function make(PostableModel $model): VoucherData;
}
