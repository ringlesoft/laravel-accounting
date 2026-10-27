<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\Report;
use RingleSoft\LaravelAccounting\Support\Money;

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
        $totalDebits = Money::ZERO;
        $totalCredits = Money::ZERO;

        foreach ($accounts as $account) {
            $balance = $this->balanceService->getAccountBalance($account, $asOfDate);

            if (Money::isZero($balance)) {
                continue;
            }

            $normalBalance = $account->accountType->normal_balance;
            $isAbnormal = Money::isNegative($balance);

            if ($normalBalance === 'debit') {
                $debitBalance = $isAbnormal ? Money::ZERO : $balance;
                $creditBalance = $isAbnormal ? Money::abs($balance) : Money::ZERO;
            } else {
                $creditBalance = $isAbnormal ? Money::ZERO : $balance;
                $debitBalance = $isAbnormal ? Money::abs($balance) : Money::ZERO;
            }

            $accountBalances[] = [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'account_type' => $account->accountType->name,
                'debit_balance' => $debitBalance,
                'credit_balance' => $creditBalance,
            ];

            $totalDebits = Money::add($totalDebits, $debitBalance);
            $totalCredits = Money::add($totalCredits, $creditBalance);
        }

        $isBalanced = Money::equals($totalDebits, $totalCredits);
        $difference = Money::sub($totalDebits, $totalCredits);

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
