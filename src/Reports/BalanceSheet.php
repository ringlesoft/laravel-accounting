<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum;
use RingleSoft\LaravelAccounting\Models\Report;

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
                'assets' => ['accounts' => [], 'total' => 0],
                'liabilities' => ['accounts' => [], 'total' => 0],
                'equity' => ['accounts' => [], 'total' => 0],
                'total_liabilities_and_equity' => 0,
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

        $totalAssets = $assetBalances->sum('balance');
        $totalLiabilities = $liabilityBalances->sum('balance');
        $totalEquity = $equityBalances->sum('balance');

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
            'total_liabilities_and_equity' => $totalLiabilities + $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ]);

        return $this;
    }
}
