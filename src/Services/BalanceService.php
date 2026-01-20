<?php

namespace RingleSoft\LaravelAccounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelAccounting\Enums\NormalBalance;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\AccountBalance;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;
use RingleSoft\LaravelAccounting\Models\JournalEntryItem;
use Throwable;

class BalanceService
{
    /**
     * Get the balance of an account as of a specific date
     */
    public function getAccountBalance(Account $account, ?Carbon $asOfDate = null): float
    {
        $asOfDate = $asOfDate ?? now();

        $items = JournalEntryItem::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($query) use ($asOfDate) {
                $query->where('is_posted', true)
                    ->where('transaction_date', '<=', $asOfDate)
                    ->where('is_voided', false);
            })
            ->get();

        $totalDebits = $items->sum('debit_amount');
        $totalCredits = $items->sum('credit_amount');

        $normalBalance = $account->accountType->normal_balance;

        if ($normalBalance === NormalBalance::DEBIT->value) {
            return $totalDebits - $totalCredits;
        }

        return $totalCredits - $totalDebits;
    }

    /**
     * Get trial balance for a fiscal period
     */
    public function getTrialBalance(?FiscalPeriod $period = null): Collection
    {
        $query = Account::with('accountType')->where('is_active', true);

        if ($period) {
            $endDate = $period->end_date;
        } else {
            $endDate = now();
        }

        $accounts = $query->get();

        return $accounts->map(function ($account) use ($endDate) {
            $balance = $this->getAccountBalance($account, $endDate);

            return [
                'account_id' => $account->id,
                'account_code' => $account->code,
                'account_name' => $account->name,
                'account_type' => $account->accountType->name,
                'normal_balance' => $account->accountType->normal_balance,
                'debit_balance' => $balance >= 0 && $account->accountType->normal_balance === NormalBalance::DEBIT->value ? $balance : 0,
                'credit_balance' => $balance >= 0 && $account->accountType->normal_balance === NormalBalance::CREDIT->value ? $balance : 0,
                'balance' => $balance,
            ];
        })->filter(function ($item) {
            return abs($item['balance']) > 0.01;
        })->values();
    }

    /**
     * Get account movement for a specific period
     */
    public function getPeriodMovement(Account $account, FiscalPeriod $period): array
    {
        $items = JournalEntryItem::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($query) use ($period) {
                $query->where('is_posted', true)
                    ->where('fiscal_period_id', $period->id)
                    ->where('is_voided', false);
            })
            ->get();

        $totalDebits = $items->sum('debit_amount');
        $totalCredits = $items->sum('credit_amount');

        $openingBalance = $this->getAccountBalance($account, $period->start_date->subDay());
        $closingBalance = $this->getAccountBalance($account, $period->end_date);

        return [
            'account_id' => $account->id,
            'account_code' => $account->code,
            'account_name' => $account->name,
            'period_id' => $period->id,
            'period_name' => $period->name,
            'opening_balance' => $openingBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'net_movement' => $totalDebits - $totalCredits,
            'closing_balance' => $closingBalance,
        ];
    }

    /**
     * Update cached balances for a fiscal period
     *
     * @throws Throwable
     */
    public function updateCachedBalances(FiscalPeriod $period): void
    {
        DB::transaction(function () use ($period) {
            $accounts = Account::where('is_active', true)->get();

            foreach ($accounts as $account) {
                $movement = $this->getPeriodMovement($account, $period);

                AccountBalance::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'fiscal_period_id' => $period->id,
                    ],
                    [
                        'opening_balance' => $movement['opening_balance'],
                        'debit_total' => $movement['total_debits'],
                        'credit_total' => $movement['total_credits'],
                        'closing_balance' => $movement['closing_balance'],
                    ]
                );
            }
        });
    }

    /**
     * Get opening balance for an account in a period
     */
    public function getOpeningBalance(Account $account, FiscalPeriod $period): float
    {
        $cachedBalance = AccountBalance::where('account_id', $account->id)
            ->where('fiscal_period_id', $period->id)
            ->first();

        if ($cachedBalance) {
            return $cachedBalance->opening_balance;
        }

        return $this->getAccountBalance($account, $period->start_date->subDay());
    }

    /**
     * Get closing balance for an account in a period
     */
    public function getClosingBalance(Account $account, FiscalPeriod $period): float
    {
        $cachedBalance = AccountBalance::where('account_id', $account->id)
            ->where('fiscal_period_id', $period->id)
            ->first();

        if ($cachedBalance) {
            return $cachedBalance->closing_balance;
        }

        return $this->getAccountBalance($account, $period->end_date);
    }

    /**
     * Verify trial balance is balanced
     */
    public function verifyTrialBalance(?FiscalPeriod $period = null): array
    {
        $trialBalance = $this->getTrialBalance($period);

        $totalDebits = $trialBalance->sum('debit_balance');
        $totalCredits = $trialBalance->sum('credit_balance');

        $isBalanced = abs($totalDebits - $totalCredits) < 0.01;

        return [
            'is_balanced' => $isBalanced,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'difference' => $totalDebits - $totalCredits,
            'period' => $period?->name,
        ];
    }

    /**
     * Get account balances by type
     */
    public function getBalancesByType(string $accountType, ?Carbon $asOfDate = null): Collection
    {
        $asOfDate = $asOfDate ?? now();

        $accounts = Account::whereHas('accountType', function ($query) use ($accountType) {
            $query->where('code', $accountType);
        })->where('is_active', true)->get();

        return $accounts->map(function ($account) use ($asOfDate) {
            return [
                'account_id' => $account->id,
                'account_code' => $account->code,
                'account_name' => $account->name,
                'balance' => $this->getAccountBalance($account, $asOfDate),
            ];
        })->filter(function ($item) {
            return abs($item['balance']) > 0.01;
        })->values();
    }
}
