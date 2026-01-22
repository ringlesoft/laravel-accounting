<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelAccounting\Enums\AccountType as AccountTypeEnum;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Models\JournalEntryItem;
use RingleSoft\LaravelAccounting\Models\Report;

class IncomeStatement extends Report
{
    public function generate(): self
    {
        $startDate = $this->getParameter('start_date', now()->startOfMonth());
        $endDate = $this->getParameter('end_date', now()->endOfMonth());

        if (is_string($startDate)) {
            $startDate = Carbon::parse($startDate);
        }
        if (is_string($endDate)) {
            $endDate = Carbon::parse($endDate);
        }

        $this->setTitle('Income Statement');

        $incomeTypeId = AccountTypeEnum::INCOME->getId();
        $expenseTypeId = AccountTypeEnum::EXPENSE->getId();

        if (!$incomeTypeId || !$expenseTypeId) {
            $this->setData([
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'revenue' => ['accounts' => [], 'total' => 0],
                'expenses' => ['accounts' => [], 'total' => 0],
                'net_income' => 0,
            ]);
            return $this;
        }

        $prefix = config('laravel_accounting.table_prefix', 'la');

        $revenueAccounts = $this->getAccountBalances($prefix, $incomeTypeId, $startDate, $endDate);
        $expenseAccounts = $this->getAccountBalances($prefix, $expenseTypeId, $startDate, $endDate);

        $revenue = [];
        $totalRevenue = 0;

        foreach ($revenueAccounts as $account) {
            $amount = $account->total_credits - $account->total_debits;

            if (abs($amount) < 0.01) {
                continue;
            }

            $revenue[] = [
                'account_id' => $account->account_id,
                'code' => $account->code,
                'name' => $account->name,
                'amount' => (float) $amount,
            ];
            $totalRevenue += $amount;
        }

        $expenses = [];
        $totalExpenses = 0;

        foreach ($expenseAccounts as $account) {
            $amount = $account->total_debits - $account->total_credits;

            if (abs($amount) < 0.01) {
                continue;
            }

            $expenses[] = [
                'account_id' => $account->account_id,
                'code' => $account->code,
                'name' => $account->name,
                'amount' => (float) $amount,
            ];
            $totalExpenses += $amount;
        }

        $netIncome = $totalRevenue - $totalExpenses;

        $this->setData([
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'revenue' => [
                'accounts' => $revenue,
                'total' => $totalRevenue,
            ],
            'expenses' => [
                'accounts' => $expenses,
                'total' => $totalExpenses,
            ],
            'net_income' => $netIncome,
        ]);

        return $this;
    }

    /**
     * Get account balances for a specific account type
     */
    protected function getAccountBalances(string $prefix, int $accountTypeId, Carbon $startDate, Carbon $endDate)
    {
        $accountsTable = (new Account())->getTable();
        $journalEntryItemsTable = (new JournalEntryItem())->getTable();
        $journalEntriesTable = (new JournalEntry())->getTable();
        return DB::table("$accountsTable as acc")
            ->leftJoin("{$journalEntryItemsTable} as jei", 'acc.id', '=', 'jei.account_id')
            ->leftJoin("{$journalEntriesTable} as je", function ($join) use ($startDate, $endDate) {
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
    }
}
