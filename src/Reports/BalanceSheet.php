<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use RingleSoft\LaravelAccounting\Models\Account;
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

        $accounts = Account::with('accountType')
            ->where('is_active', true)
            ->get();

        $assets = [];
        $liabilities = [];
        $equity = [];

        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;

        foreach ($accounts as $account) {
            $balance = $this->balanceService->getAccountBalance($account, $asOfDate);

            if (abs($balance) < 0.01) {
                continue;
            }

            $accountData = [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'balance' => $balance,
            ];

            $accountTypeCode = $account->accountType->code;

            if (str_starts_with($accountTypeCode, 'ASSET')) {
                $assets[] = $accountData;
                $totalAssets += $balance;
            } elseif (str_starts_with($accountTypeCode, 'LIABILITY')) {
                $liabilities[] = $accountData;
                $totalLiabilities += $balance;
            } elseif (str_starts_with($accountTypeCode, 'EQUITY')) {
                $equity[] = $accountData;
                $totalEquity += $balance;
            }
        }

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
