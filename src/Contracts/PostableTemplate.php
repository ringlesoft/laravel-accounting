<?php

namespace RingleSoft\LaravelAccounting\Contracts;

use Closure;
use Illuminate\Support\Carbon;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Models\Account;

interface PostableTemplate
{
    /**
     * Configure the template (Add debit/credit entries)
     */
    public function configure(): void;

    /**
     * Add a debit entry to the template
     *
     * @param  Closure(string): (string|int|float)|null  $amount  receives the postable amount as a Money::SCALE numeric string
     */
    public function debit(AccountPresetContract $account, ?Closure $amount, Closure|string|null $memo = null): self;

    /**
     * Add a credit entry to the template
     *
     * @param  Closure(string): (string|int|float)|null  $amount  receives the postable amount as a Money::SCALE numeric string
     */
    public function credit(AccountPresetContract $account, ?Closure $amount, Closure|string|null $memo = null): self;

    /**
     * Explicitly resolve a preset to a specific account, bypassing the
     * default preset lookup for that entry. Use this to pick one child
     * account under an ambiguous parent/category preset (e.g.
     * COST_OF_GOODS_SOLD with several children), or to target a fully
     * custom account that carries no preset at all.
     *
     * @param  Account|int|string  $account  An Account instance, or its id/UUID
     */
    public function resolveAccount(AccountPresetContract $preset, Account|int|string $account): self;

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

    /**
     * Validate the template's own business rules against the model before its
     * entries are resolved to real accounts. A no-op by default - override to
     * reject an invalid model/parameter combination without needing to override
     * make() itself.
     *
     * @throws InvalidVoucherException
     */
    public function validate(PostableModel $model): void;
}
