<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
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

        $netIncome = $this->balanceService->calculateNetIncome($startDate, $endDate);

        $operating = $this->calculateOperatingActivities($startDate, $endDate, $netIncome);
        $investing = $this->calculateInvestingActivities($startDate, $endDate);
        $financing = $this->calculateFinancingActivities($startDate, $endDate);

        $netCashFlow = $operating['total'] + $investing['total'] + $financing['total'];

        $beginningCash = $this->balanceService->getCashAndCashEquivalentsBalance($startDate->copy()->subDay());
        $endingCash = $this->balanceService->getCashAndCashEquivalentsBalance($endDate);

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

    /**
     * Change in each account's balance between two dates, using the existing
     * getAccountBalance() sign convention (positive = normal-side balance).
     * Shared by every "changes in X over the period" section below.
     */
    protected function balanceChange(Account $account, Carbon $startDate, Carbon $endDate): float
    {
        $beginningBalance = $this->balanceService->getAccountBalance($account, $startDate->copy()->subDay());
        $endingBalance = $this->balanceService->getAccountBalance($account, $endDate);

        return $endingBalance - $beginningBalance;
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

        // Cash itself is reconciled separately via beginning/ending cash, so it
        // must be excluded here or its movement would be double-counted.
        $cashAccountIds = $this->balanceService->getAccountsForPresetGroup(AccountPreset::CASH_AND_CASH_EQUIVALENT)
            ->pluck('id');

        $currentAssets = Account::where('account_type_id', $assetTypeId)
            ->where('is_active', true)
            ->where('category', 'Current Assets')
            ->whereNotIn('id', $cashAccountIds)
            ->get();

        foreach ($currentAssets as $account) {
            $change = $this->balanceChange($account, $startDate, $endDate);

            if (abs($change) > 0.01) {
                $adjustments[] = [
                    'description' => "Change in {$account->name}",
                    'amount' => -$change,
                ];
                $total -= $change;
            }
        }

        $currentLiabilities = Account::where('account_type_id', $liabilityTypeId)
            ->where('is_active', true)
            ->where('category', 'Current Liabilities')
            ->get();

        foreach ($currentLiabilities as $account) {
            $change = $this->balanceChange($account, $startDate, $endDate);

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

        $fixedAssets = $this->balanceService->getAccountsForPresetGroup(AccountPreset::FIXED_ASSETS);

        foreach ($fixedAssets as $account) {
            $change = $this->balanceChange($account, $startDate, $endDate);

            if (abs($change) > 0.01) {
                $activities[] = [
                    'description' => $change > 0 ? "Purchase of {$account->name}" : "Sale of {$account->name}",
                    'amount' => -$change,
                ];
                $total -= $change;
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

        $longTermLiabilities = Account::where('account_type_id', $liabilityTypeId)
            ->where('is_active', true)
            ->where('category', 'Long-term Liabilities')
            ->get();

        foreach ($longTermLiabilities as $account) {
            $change = $this->balanceChange($account, $startDate, $endDate);

            if (abs($change) > 0.01) {
                $activities[] = [
                    'description' => $change > 0 ? "Proceeds from {$account->name}" : "Repayment of {$account->name}",
                    'amount' => $change,
                ];
                $total += $change;
            }
        }

        $equityAccounts = Account::where('account_type_id', $equityTypeId)
            ->where('is_active', true)
            ->get();

        foreach ($equityAccounts as $account) {
            $change = $this->balanceChange($account, $startDate, $endDate);

            if (abs($change) > 0.01) {
                $activities[] = [
                    'description' => $change > 0 ? 'Capital contribution' : 'Dividends paid',
                    'amount' => $change,
                ];
                $total += $change;
            }
        }

        return [
            'activities' => $activities,
            'total' => $total,
        ];
    }
}
