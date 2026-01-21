<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\Report;

class TrialBalance extends Report
{
    public function generate(): self
    {
        $asOfDate = $this->getParameter('as_of_date', now());
        if (is_string($asOfDate)) {
            $asOfDate = Carbon::parse($asOfDate);
        }

        $this->setTitle('Trial Balance');

        $accounts = Account::with('accountType')
            ->where('is_active', true)
            ->get();

        $accountBalances = [];
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            $balance = $this->balanceService->getAccountBalance($account, $asOfDate);

            if (abs($balance) < 0.01) {
                continue;
            }

            $normalBalance = $account->accountType->normal_balance;
            $debitBalance = 0;
            $creditBalance = 0;

            if ($normalBalance === 'debit') {
                $debitBalance = $balance >= 0 ? $balance : 0;
                $creditBalance = $balance < 0 ? abs($balance) : 0;
            } else {
                $creditBalance = $balance >= 0 ? $balance : 0;
                $debitBalance = $balance < 0 ? abs($balance) : 0;
            }

            $accountBalances[] = [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'account_type' => $account->accountType->name,
                'debit_balance' => $debitBalance,
                'credit_balance' => $creditBalance,
            ];

            $totalDebits += $debitBalance;
            $totalCredits += $creditBalance;
        }

        $isBalanced = abs($totalDebits - $totalCredits) < 0.01;
        $difference = $totalDebits - $totalCredits;

        $this->setData([
            'as_of_date' => $asOfDate->toDateString(),
            'accounts' => $accountBalances,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'difference' => $difference,
            'is_balanced' => $isBalanced,
        ]);

        return $this;
    }
}
