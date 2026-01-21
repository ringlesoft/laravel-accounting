<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\JournalEntryItem;
use RingleSoft\LaravelAccounting\Models\Report;

class CashFlowStatement extends Report
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

        $this->setTitle('Cash Flow Statement (Indirect Method)');

        $netIncome = $this->calculateNetIncome($startDate, $endDate);

        $operating = $this->calculateOperatingActivities($startDate, $endDate, $netIncome);
        $investing = $this->calculateInvestingActivities($startDate, $endDate);
        $financing = $this->calculateFinancingActivities($startDate, $endDate);

        $netCashFlow = $operating['total'] + $investing['total'] + $financing['total'];

        $cashAccount = Account::whereHas('accountType', function ($query) {
            $query->where('code', 'ASSET_CURRENT_CASH');
        })->first();

        $beginningCash = 0;
        $endingCash = 0;

        if ($cashAccount) {
            $beginningCash = $this->balanceService->getAccountBalance($cashAccount, $startDate->copy()->subDay());
            $endingCash = $this->balanceService->getAccountBalance($cashAccount, $endDate);
        }

        $this->setData([
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'operating_activities' => $operating,
            'investing_activities' => $investing,
            'financing_activities' => $financing,
            'net_cash_flow' => $netCashFlow,
            'beginning_cash' => $beginningCash,
            'ending_cash' => $endingCash,
            'reconciliation_difference' => abs(($beginningCash + $netCashFlow) - $endingCash),
        ]);

        return $this;
    }

    protected function calculateNetIncome(Carbon $startDate, Carbon $endDate): float
    {
        $revenueAccounts = Account::whereHas('accountType', function ($query) {
            $query->where('code', 'LIKE', 'REVENUE%');
        })->get();

        $expenseAccounts = Account::whereHas('accountType', function ($query) {
            $query->where('code', 'LIKE', 'EXPENSE%');
        })->get();

        $totalRevenue = 0;
        $totalExpenses = 0;

        foreach ($revenueAccounts as $account) {
            $items = JournalEntryItem::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                    $query->where('is_posted', true)
                        ->where('is_voided', false)
                        ->whereBetween('transaction_date', [$startDate, $endDate]);
                })
                ->get();

            $totalRevenue += $items->sum('credit_amount') - $items->sum('debit_amount');
        }

        foreach ($expenseAccounts as $account) {
            $items = JournalEntryItem::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                    $query->where('is_posted', true)
                        ->where('is_voided', false)
                        ->whereBetween('transaction_date', [$startDate, $endDate]);
                })
                ->get();

            $totalExpenses += $items->sum('debit_amount') - $items->sum('credit_amount');
        }

        return $totalRevenue - $totalExpenses;
    }

    protected function calculateOperatingActivities(Carbon $startDate, Carbon $endDate, float $netIncome): array
    {
        $adjustments = [];
        $total = $netIncome;

        $currentAssets = Account::whereHas('accountType', function ($query) {
            $query->where('code', 'LIKE', 'ASSET_CURRENT%')
                ->where('code', '!=', 'ASSET_CURRENT_CASH');
        })->get();

        $currentLiabilities = Account::whereHas('accountType', function ($query) {
            $query->where('code', 'LIKE', 'LIABILITY_CURRENT%');
        })->get();

        foreach ($currentAssets as $account) {
            $beginningBalance = $this->balanceService->getAccountBalance($account, $startDate->copy()->subDay());
            $endingBalance = $this->balanceService->getAccountBalance($account, $endDate);
            $change = $endingBalance - $beginningBalance;

            if (abs($change) > 0.01) {
                $adjustments[] = [
                    'description' => "Change in {$account->name}",
                    'amount' => -$change,
                ];
                $total -= $change;
            }
        }

        foreach ($currentLiabilities as $account) {
            $beginningBalance = $this->balanceService->getAccountBalance($account, $startDate->copy()->subDay());
            $endingBalance = $this->balanceService->getAccountBalance($account, $endDate);
            $change = $endingBalance - $beginningBalance;

            if (abs($change) > 0.01) {
                $adjustments[] = [
                    'description' => "Change in {$account->name}",
                    'amount' => $change,
                ];
                $total += $change;
            }
        }

        return [
            'net_income' => $netIncome,
            'adjustments' => $adjustments,
            'total' => $total,
        ];
    }

    protected function calculateInvestingActivities(Carbon $startDate, Carbon $endDate): array
    {
        $activities = [];
        $total = 0;

        $fixedAssets = Account::whereHas('accountType', function ($query) {
            $query->where('code', 'LIKE', 'ASSET_FIXED%');
        })->get();

        foreach ($fixedAssets as $account) {
            $items = JournalEntryItem::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                    $query->where('is_posted', true)
                        ->where('is_voided', false)
                        ->whereBetween('transaction_date', [$startDate, $endDate]);
                })
                ->get();

            $netChange = $items->sum('debit_amount') - $items->sum('credit_amount');

            if (abs($netChange) > 0.01) {
                $activities[] = [
                    'description' => $netChange > 0 ? "Purchase of {$account->name}" : "Sale of {$account->name}",
                    'amount' => -$netChange,
                ];
                $total -= $netChange;
            }
        }

        return [
            'activities' => $activities,
            'total' => $total,
        ];
    }

    protected function calculateFinancingActivities(Carbon $startDate, Carbon $endDate): array
    {
        $activities = [];
        $total = 0;

        $longTermLiabilities = Account::whereHas('accountType', function ($query) {
            $query->where('code', 'LIKE', 'LIABILITY_LONG_TERM%');
        })->get();

        $equityAccounts = Account::whereHas('accountType', function ($query) {
            $query->where('code', 'LIKE', 'EQUITY%');
        })->get();

        foreach ($longTermLiabilities as $account) {
            $items = JournalEntryItem::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                    $query->where('is_posted', true)
                        ->where('is_voided', false)
                        ->whereBetween('transaction_date', [$startDate, $endDate]);
                })
                ->get();

            $netChange = $items->sum('credit_amount') - $items->sum('debit_amount');

            if (abs($netChange) > 0.01) {
                $activities[] = [
                    'description' => $netChange > 0 ? "Proceeds from {$account->name}" : "Repayment of {$account->name}",
                    'amount' => $netChange,
                ];
                $total += $netChange;
            }
        }

        foreach ($equityAccounts as $account) {
            $items = JournalEntryItem::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                    $query->where('is_posted', true)
                        ->where('is_voided', false)
                        ->whereBetween('transaction_date', [$startDate, $endDate]);
                })
                ->get();

            $netChange = $items->sum('credit_amount') - $items->sum('debit_amount');

            if (abs($netChange) > 0.01) {
                $activities[] = [
                    'description' => $netChange > 0 ? "Capital contribution" : "Dividends paid",
                    'amount' => $netChange,
                ];
                $total += $netChange;
            }
        }

        return [
            'activities' => $activities,
            'total' => $total,
        ];
    }
}
