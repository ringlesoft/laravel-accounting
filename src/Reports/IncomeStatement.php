<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use RingleSoft\LaravelAccounting\Models\Account;
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

        $accounts = Account::with('accountType')
            ->where('is_active', true)
            ->get();

        $revenue = [];
        $expenses = [];

        $totalRevenue = 0;
        $totalExpenses = 0;

        foreach ($accounts as $account) {
            $accountTypeCode = $account->accountType->code;

            if (!str_starts_with($accountTypeCode, 'REVENUE') && !str_starts_with($accountTypeCode, 'EXPENSE')) {
                continue;
            }

            $items = JournalEntryItem::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                    $query->where('is_posted', true)
                        ->where('is_voided', false)
                        ->whereBetween('transaction_date', [$startDate, $endDate]);
                })
                ->get();

            $totalDebits = $items->sum('debit_amount');
            $totalCredits = $items->sum('credit_amount');

            $amount = 0;
            if (str_starts_with($accountTypeCode, 'REVENUE')) {
                $amount = $totalCredits - $totalDebits;
            } else {
                $amount = $totalDebits - $totalCredits;
            }

            if (abs($amount) < 0.01) {
                continue;
            }

            $accountData = [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'amount' => $amount,
            ];

            if (str_starts_with($accountTypeCode, 'REVENUE')) {
                $revenue[] = $accountData;
                $totalRevenue += $amount;
            } else {
                $expenses[] = $accountData;
                $totalExpenses += $amount;
            }
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
}
