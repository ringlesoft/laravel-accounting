<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum;
use RingleSoft\LaravelAccounting\Models\Report;
use RingleSoft\LaravelAccounting\Support\Money;

class BalanceSheet extends Report
{
    public function generate(): self
    {
        $asOfDate = $this->getParameter('as_of_date', now());
        if (is_string($asOfDate)) {
            $asOfDate = Carbon::parse($asOfDate);
        }

        $this->setTitle('Balance Sheet');

        $assetType = AccountTypeEnum::ASSET->accountType();
        $liabilityType = AccountTypeEnum::LIABILITY->accountType();
        $equityType = AccountTypeEnum::EQUITY->accountType();

        if (! $assetType || ! $liabilityType || ! $equityType) {
            $this->setData([
                'as_of_date' => $asOfDate->toDateString(),
                'assets' => ['accounts' => [], 'total' => Money::ZERO],
                'liabilities' => ['accounts' => [], 'total' => Money::ZERO],
                'equity' => ['accounts' => [], 'total' => Money::ZERO],
                'total_liabilities_and_equity' => Money::ZERO,
                'is_balanced' => true,
            ]);

            return $this;
        }

        $assetBalances = $this->balanceService->getBalancesByAccountType($assetType, $asOfDate);
        $liabilityBalances = $this->balanceService->getBalancesByAccountType($liabilityType, $asOfDate);
        $equityBalances = $this->balanceService->getBalancesByAccountType($equityType, $asOfDate);

        $assets = $assetBalances->map(fn ($item) => [
            'account_id' => $item['account_id'],
            'code' => $item['code'],
            'name' => $item['name'],
            'balance' => $item['balance'],
        ])->toArray();

        $liabilities = $liabilityBalances->map(fn ($item) => [
            'account_id' => $item['account_id'],
            'code' => $item['code'],
            'name' => $item['name'],
            'balance' => $item['balance'],
        ])->toArray();

        $equity = $equityBalances->map(fn ($item) => [
            'account_id' => $item['account_id'],
            'code' => $item['code'],
            'name' => $item['name'],
            'balance' => $item['balance'],
        ])->toArray();

        $totalAssets = Money::sum($assetBalances->pluck('balance'));
        $totalLiabilities = Money::sum($liabilityBalances->pluck('balance'));
        $totalEquity = Money::sum($equityBalances->pluck('balance'));
        $totalLiabilitiesAndEquity = Money::add($totalLiabilities, $totalEquity);

        $this->setData([
            'as_of_date' => $asOfDate->toDateString(),
            'assets' => [
                'accounts' => $assets,
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'accounts' => $liabilities,
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'accounts' => $equity,
                'total' => $totalEquity,
            ],
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'is_balanced' => Money::equals($totalAssets, $totalLiabilitiesAndEquity),
        ]);

        return $this;
    }
}
