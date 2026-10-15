<?php

namespace RingleSoft\LaravelAccounting\DTOs;

use Closure;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Enums\EntryType;

class AccountTemplateEntry
{
    /**
     * @param  Closure(float $amount): float|float|null  $amount  function that manipulates the amount fetched from the model's `getPostableAmount()` method
     * @param  Closure(): string|string|null  $memo  the memo for the specific entry
     */
    public function __construct(
        public EntryType $type,
        public AccountPreset $account,
        public Closure|float|null $amount = null,
        public Closure|string|null $memo = null
    ) {}

    public static function credit(AccountPreset $account, Closure|float|null $amount = null, Closure|string|null $memo = null): self
    {
        return new self(EntryType::CREDIT, $account, $amount, $memo);
    }

    public static function debit(AccountPreset $account, Closure|float|null $amount = null, Closure|string|null $memo = null): self
    {
        return new self(EntryType::DEBIT, $account, $amount, $memo);
    }

    /**
     * Resolve the amount from the closure
     *
     * @param  float  $amount  the amount fetched from the model's `getPostableAmount()` method
     */
    public function getAmount(float $amount): float
    {
        if (is_callable($this->amount)) {
            return (float) ($this->amount)($amount);
        }

        return $this->amount ?? $amount;
    }
}
