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
     */
    public function configure(): void;

    /**
     * Add a debit entry to the template
     *
     * @param  Closure(float): float|null  $amount
     */
    public function debit(AccountPreset $account, ?Closure $amount, Closure|string|null $memo = null): self;

    /**
     * Add a credit entry to the template
     *
     * @param  Closure(float): float|null  $amount
     */
    public function credit(AccountPreset $account, ?Closure $amount, Closure|string|null $memo = null): self;

    /**
     * Set the description of the voucher
     *
     * @param  Closure(PostableModel $model): string|string  $description
     */
    public function describe(Closure|string $description): self;

    /**
     * Set the date of the voucher
     *
     * @param  Closure(PostableModel $model): Carbon|Carbon  $date
     */
    public function date(Closure|Carbon $date): self;

    /**
     * Generate a voucher data from the template
     */
    public function make(PostableModel $model): VoucherData;
}
