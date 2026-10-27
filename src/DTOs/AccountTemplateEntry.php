<?php

namespace RingleSoft\LaravelAccounting\DTOs;

use Closure;
use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\EntryType;
use RingleSoft\LaravelAccounting\Support\Money;

class AccountTemplateEntry
{
    /**
     * @param  Closure(string $amount): (string|int|float)|string|int|float|null  $amount  function that manipulates the amount fetched from the model's `getPostableAmount()` method. It receives a Money::SCALE numeric string.
     * @param  Closure(): string|string|null  $memo  the memo for the specific entry
     */
    public function __construct(
        public EntryType $type,
        public AccountPresetContract $account,
        public Closure|string|int|float|null $amount = null,
        public Closure|string|null $memo = null
    ) {}

    public static function credit(AccountPresetContract $account, Closure|string|int|float|null $amount = null, Closure|string|null $memo = null): self
    {
        return new self(EntryType::CREDIT, $account, $amount, $memo);
    }

    public static function debit(AccountPresetContract $account, Closure|string|int|float|null $amount = null, Closure|string|null $memo = null): self
    {
        return new self(EntryType::DEBIT, $account, $amount, $memo);
    }

    /**
     * Resolve the amount from the closure
     *
     * @param  string|int|float  $amount  the amount fetched from the model's `getPostableAmount()` method
     * @return string The amount as a Money::SCALE numeric string
     */
    public function getAmount(string|int|float $amount): string
    {
        $amount = Money::of($amount);

        if ($this->amount instanceof Closure) {
            return Money::of(($this->amount)($amount));
        }

        return Money::of($this->amount ?? $amount);
    }
}
