<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum;
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

        $incomeType = AccountTypeEnum::INCOME->accountType();
        $expenseType = AccountTypeEnum::EXPENSE->accountType();

        if (! $incomeType || ! $expenseType) {
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

        $revenueBalances = $this->balanceService->getPeriodBalancesByAccountType($incomeType, $startDate, $endDate);
        $expenseBalances = $this->balanceService->getPeriodBalancesByAccountType($expenseType, $startDate, $endDate);

        $revenue = $revenueBalances->map(fn ($item) => [
            'account_id' => $item['account_id'],
            'code' => $item['code'],
            'name' => $item['name'],
            'amount' => $item['balance'],
        ])->toArray();

        $expenses = $expenseBalances->map(fn ($item) => [
            'account_id' => $item['account_id'],
            'code' => $item['code'],
            'name' => $item['name'],
            'amount' => $item['balance'],
        ])->toArray();

        $totalRevenue = $revenueBalances->sum('balance');
        $totalExpenses = $expenseBalances->sum('balance');
        $netIncome = abs($totalRevenue) - abs($totalExpenses);

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
