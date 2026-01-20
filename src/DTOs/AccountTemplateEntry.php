<?php

namespace RingleSoft\LaravelAccounting\DTOs;

use Closure;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Enums\EntryType;

class AccountTemplateEntry
{
    /**
     * @param  Closure|null  $amount  function that manipulates the amount fetched from the model's `getAmount()` method
     * @param  Closure|string|null  $memo  the memo for the specific entry
     */
    public function __construct(
        public EntryType $type,
        public AccountPreset $account,
        public ?Closure $amount = null,
        public Closure|string|null $memo = null
    ) {}

    public static function credit(AccountPreset $account, ?Closure $amount = null, Closure|string|null $memo = null): self
    {
        return new self(EntryType::CREDIT, $account, $amount, $memo);
    }

    public static function debit(AccountPreset $account, ?Closure $amount = null, Closure|string|null $memo = null): self
    {
        return new self(EntryType::DEBIT, $account, $amount, $memo);
    }
}
