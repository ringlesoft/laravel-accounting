<?php

namespace RingleSoft\LaravelAccounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum;
use RingleSoft\LaravelAccounting\Enums\NormalBalance;
use RingleSoft\LaravelAccounting\Events\BalanceRecalculated;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\AccountBalance;
use RingleSoft\LaravelAccounting\Models\AccountType;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
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
    public function updateCachedBalances(FiscalPeriod $period, ?int $userId = null): void
    {
        DB::transaction(function () use ($period, $userId) {
            $accounts = Account::where('is_active', true)->get();
            $accountsUpdated = 0;

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
                $accountsUpdated++;
            }

            event(new BalanceRecalculated($period, $accountsUpdated, $userId));
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
     * Update cached balance for a specific account and period
     */
    public function updateAccountBalance(Account $account, FiscalPeriod $period): void
    {
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

    /**
     * Get balances for multiple accounts by AccountType model as of a specific date
     * Optimized for bulk retrieval in reports like Balance Sheet
     */
    public function getBalancesByAccountType(AccountType $accountType, ?Carbon $asOfDate = null): Collection
    {
        $accountTypeId = $accountType->id;
        $normalBalance = $accountType->normal_balance;
        $asOfDate = $asOfDate ?? now();

        $accountsTable = (new Account)->getTable();
        $journalEntryItemsTable = (new JournalEntryItem)->getTable();
        $journalEntriesTable = (new JournalEntry)->getTable();
        $accountTypesTable = (new AccountType)->getTable();

        $results = DB::table("$accountsTable as acc")
            ->leftJoin("$journalEntryItemsTable as jei", 'acc.id', '=', 'jei.account_id')
            ->leftJoin("$journalEntriesTable as je", function ($join) use ($asOfDate) {
                $join->on('jei.journal_entry_id', '=', 'je.id')
                    ->where('je.is_posted', '=', true)
                    ->where('je.is_voided', '=', false)
                    ->where('je.transaction_date', '<=', $asOfDate);
            })
            ->join("$accountTypesTable as at", 'acc.account_type_id', '=', 'at.id')
            ->where('acc.is_active', true)
            ->where('acc.account_type_id', $accountTypeId)
            ->select(
                'acc.id as account_id',
                'acc.code',
                'acc.name',
                'at.normal_balance',
                DB::raw('COALESCE(SUM(jei.debit_amount), 0) as total_debits'),
                DB::raw('COALESCE(SUM(jei.credit_amount), 0) as total_credits')
            )
            ->groupBy('acc.id', 'acc.code', 'acc.name', 'at.normal_balance')
            ->get();

        return $results->map(function ($item) {
            $balance = $item->normal_balance === NormalBalance::DEBIT->value
                ? $item->total_debits - $item->total_credits
                : $item->total_credits - $item->total_debits;

            return [
                'account_id' => $item->account_id,
                'code' => $item->code,
                'name' => $item->name,
                'total_debits' => (float) $item->total_debits,
                'total_credits' => (float) $item->total_credits,
                'balance' => (float) $balance,
            ];
        })->filter(function ($item) {
            return abs($item['balance']) > 0.01;
        })->values();
    }

    public function getPeriodBalancesByAccountTypeEnum(AccountTypeEnum $accountTypeEnum, Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->getPeriodBalancesByAccountType($accountTypeEnum->accountType(), $startDate, $endDate);
    }

    /**
     * Get balances for multiple accounts by AccountType model for a specific period
     * Optimized for period-based reports like Income Statement
     */
    public function getPeriodBalancesByAccountType(AccountType $accountType, Carbon $startDate, Carbon $endDate): Collection
    {
        $accountTypeId = $accountType->id;
        $normalBalance = $accountType->normal_balance;

        $accountsTable = (new Account)->getTable();
        $journalEntryItemsTable = (new JournalEntryItem)->getTable();
        $journalEntriesTable = (new JournalEntry)->getTable();

        $results = DB::table("$accountsTable as acc")
            ->leftJoin("$journalEntryItemsTable as jei", 'acc.id', '=', 'jei.account_id')
            ->leftJoin("$journalEntriesTable as je", function ($join) use ($startDate, $endDate) {
                $join->on('jei.journal_entry_id', '=', 'je.id')
                    ->where('je.is_posted', '=', true)
                    ->where('je.is_voided', '=', false)
                    ->whereBetween('je.transaction_date', [$startDate, $endDate]);
            })
            ->where('acc.is_active', true)
            ->where('acc.account_type_id', $accountTypeId)
            ->select(
                'acc.id as account_id',
                'acc.code',
                'acc.name',
                DB::raw('COALESCE(SUM(jei.debit_amount), 0) as total_debits'),
                DB::raw('COALESCE(SUM(jei.credit_amount), 0) as total_credits')
            )
            ->groupBy('acc.id', 'acc.code', 'acc.name')
            ->get();

        return $results->map(function ($item) use ($normalBalance) {
            $balance = $normalBalance === NormalBalance::DEBIT->value
                ? $item->total_debits - $item->total_credits
                : $item->total_credits - $item->total_debits;

            return [
                'account_id' => $item->account_id,
                'code' => $item->code,
                'name' => $item->name,
                'total_debits' => (float) $item->total_debits,
                'total_credits' => (float) $item->total_credits,
                'balance' => (float) $balance,
            ];
        })->filter(function ($item) {
            return abs($item['balance']) > 0.01;
        })->values();
    }

    /**
     * Get balances for accounts matching a code pattern by type ID for a specific period
     * Useful for Cash Flow Statement subcategories
     */
    public function getPeriodBalancesByAccountTypeIdAndCodePattern(
        int $accountTypeId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $codePattern = null
    ): Collection {

        $accountsTable = (new Account)->getTable();
        $journalEntryItemsTable = (new JournalEntryItem)->getTable();
        $journalEntriesTable = (new JournalEntry)->getTable();
        $accountTypesTable = (new AccountType)->getTable();

        $query = DB::table("$accountsTable as acc")
            ->leftJoin("$journalEntryItemsTable as jei", 'acc.id', '=', 'jei.account_id')
            ->leftJoin("$journalEntriesTable as je", function ($join) use ($startDate, $endDate) {
                $join->on('jei.journal_entry_id', '=', 'je.id')
                    ->where('je.is_posted', '=', true)
                    ->where('je.is_voided', '=', false)
                    ->whereBetween('je.transaction_date', [$startDate, $endDate]);
            })
            ->join("$accountTypesTable as at", 'acc.account_type_id', '=', 'at.id')
            ->where('acc.is_active', true)
            ->where('acc.account_type_id', $accountTypeId);

        if ($codePattern) {
            $query->where('acc.code', 'LIKE', $codePattern);
        }

        $results = $query
            ->select(
                'acc.id as account_id',
                'acc.code',
                'acc.name',
                'at.normal_balance',
                DB::raw('COALESCE(SUM(jei.debit_amount), 0) as total_debits'),
                DB::raw('COALESCE(SUM(jei.credit_amount), 0) as total_credits')
            )
            ->groupBy('acc.id', 'acc.code', 'acc.name', 'at.normal_balance')
            ->get();

        return $results->map(function ($item) {
            return [
                'account_id' => $item->account_id,
                'code' => $item->code,
                'name' => $item->name,
                'normal_balance' => $item->normal_balance,
                'total_debits' => (float) $item->total_debits,
                'total_credits' => (float) $item->total_credits,
            ];
        });
    }
}
