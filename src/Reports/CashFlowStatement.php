<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum;
use RingleSoft\LaravelAccounting\Models\Account;
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

        $beginningCash = 0;
        $endingCash = 0;

        $assetTypeId = AccountTypeEnum::ASSET->getId();
        if ($assetTypeId) {
            $prefix = config('laravel_accounting.table_prefix', 'la');
            $cashAccount = DB::table("{$prefix}_accounts as acc")
                ->where('acc.account_type_id', $assetTypeId)
                ->where('acc.is_active', true)
                ->where(function ($query) {
                    $query->where('acc.code', 'LIKE', '%CASH%')
                        ->orWhere('acc.name', 'LIKE', '%Cash%');
                })
                ->select('acc.id')
                ->first();

            if ($cashAccount) {
                $account = Account::find($cashAccount->id);
                if ($account) {
                    $beginningCash = $this->balanceService->getAccountBalance($account, $startDate->copy()->subDay());
                    $endingCash = $this->balanceService->getAccountBalance($account, $endDate);
                }
            }
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
        $incomeType = AccountTypeEnum::INCOME->accountType();
        $expenseType = AccountTypeEnum::EXPENSE->accountType();

        if (! $incomeType || ! $expenseType) {
            return 0;
        }

        $revenueBalances = $this->balanceService->getPeriodBalancesByAccountType($incomeType, $startDate, $endDate);
        $expenseBalances = $this->balanceService->getPeriodBalancesByAccountType($expenseType, $startDate, $endDate);

        return $revenueBalances->sum('balance') - $expenseBalances->sum('balance');
    }

    protected function calculateOperatingActivities(Carbon $startDate, Carbon $endDate, float $netIncome): array
    {
        $adjustments = [];
        $total = $netIncome;

        $assetTypeId = AccountTypeEnum::ASSET->getId();
        $liabilityTypeId = AccountTypeEnum::LIABILITY->getId();

        if (! $assetTypeId || ! $liabilityTypeId) {
            return [
                'net_income' => $netIncome,
                'adjustments' => $adjustments,
                'total' => $total,
            ];
        }

        $prefix = config('laravel_accounting.table_prefix', 'la');

        $currentAssets = DB::table("{$prefix}_accounts as acc")
            ->where('acc.account_type_id', $assetTypeId)
            ->where('acc.is_active', true)
            ->where('acc.code', 'LIKE', '%CURRENT%')
            ->where('acc.code', 'NOT LIKE', '%CASH%')
            ->select('acc.id', 'acc.name')
            ->get();

        foreach ($currentAssets as $accountData) {
            $account = Account::find($accountData->id);
            if ($account) {
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
        }

        $currentLiabilities = DB::table("{$prefix}_accounts as acc")
            ->where('acc.account_type_id', $liabilityTypeId)
            ->where('acc.is_active', true)
            ->where('acc.code', 'LIKE', '%CURRENT%')
            ->select('acc.id', 'acc.name')
            ->get();

        foreach ($currentLiabilities as $accountData) {
            $account = Account::find($accountData->id);
            if ($account) {
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

        $assetTypeId = AccountTypeEnum::ASSET->getId();

        if (! $assetTypeId) {
            return ['activities' => $activities, 'total' => $total];
        }

        $fixedAssets = $this->balanceService->getPeriodBalancesByAccountTypeIdAndCodePattern(
            $assetTypeId,
            $startDate,
            $endDate,
            '%FIXED%'
        );

        foreach ($fixedAssets as $account) {
            $netChange = $account['total_debits'] - $account['total_credits'];

            if (abs($netChange) > 0.01) {
                $activities[] = [
                    'description' => $netChange > 0 ? "Purchase of {$account['name']}" : "Sale of {$account['name']}",
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

        $liabilityTypeId = AccountTypeEnum::LIABILITY->getId();
        $equityTypeId = AccountTypeEnum::EQUITY->getId();

        if (! $liabilityTypeId || ! $equityTypeId) {
            return ['activities' => $activities, 'total' => $total];
        }

        $longTermLiabilities = $this->balanceService->getPeriodBalancesByAccountTypeIdAndCodePattern(
            $liabilityTypeId,
            $startDate,
            $endDate,
            '%LONG%'
        );

        foreach ($longTermLiabilities as $account) {
            $netChange = $account['total_credits'] - $account['total_debits'];

            if (abs($netChange) > 0.01) {
                $activities[] = [
                    'description' => $netChange > 0 ? "Proceeds from {$account['name']}" : "Repayment of {$account['name']}",
                    'amount' => $netChange,
                ];
                $total += $netChange;
            }
        }

        $equityAccounts = $this->balanceService->getPeriodBalancesByAccountTypeIdAndCodePattern(
            $equityTypeId,
            $startDate,
            $endDate
        );

        foreach ($equityAccounts as $account) {
            $netChange = $account['total_credits'] - $account['total_debits'];

            if (abs($netChange) > 0.01) {
                $activities[] = [
                    'description' => $netChange > 0 ? 'Capital contribution' : 'Dividends paid',
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
