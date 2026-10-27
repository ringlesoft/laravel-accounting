<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\JournalEntryItem;
use RingleSoft\LaravelAccounting\Models\Report;
use RingleSoft\LaravelAccounting\Support\Money;

class AccountLedger extends Report
{
    public function generate(): self
    {
        $accountId = $this->getParameter('account_id');
        $startDate = $this->getParameter('start_date', now()->startOfMonth());
        $endDate = $this->getParameter('end_date', now()->endOfMonth());

        if (is_string($startDate)) {
            $startDate = Carbon::parse($startDate);
        }
        if (is_string($endDate)) {
            $endDate = Carbon::parse($endDate);
        }

        if (! $accountId) {
            throw new \InvalidArgumentException('account_id parameter is required for Account Ledger report');
        }

        $account = Account::with('accountType')->findOrFail($accountId);

        $this->setTitle("Account Ledger - {$account->code} {$account->name}");

        $openingBalance = $this->balanceService->getAccountBalance($account, $startDate->copy()->subDay());

        $items = JournalEntryItem::with(['journalEntry'])
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($startDate, $endDate) {
                $query->where('is_posted', true)
                    ->where('is_voided', false)
                    ->whereBetween('transaction_date', [$startDate, $endDate]);
            })
            ->get()
            ->sortBy(function ($item) {
                return $item->journalEntry->transaction_date->timestamp;
            });

        $transactions = [];
        $runningBalance = $openingBalance;
        $totalDebits = Money::ZERO;
        $totalCredits = Money::ZERO;

        $normalBalance = $account->accountType->normal_balance;

        foreach ($items as $item) {
            if ($normalBalance === 'debit') {
                $balanceChange = Money::sub($item->debit_amount, $item->credit_amount);
            } else {
                $balanceChange = Money::sub($item->credit_amount, $item->debit_amount);
            }

            $runningBalance = Money::add($runningBalance, $balanceChange);

            $transactions[] = [
                'date' => $item->journalEntry->transaction_date->toDateString(),
                'entry_id' => $item->journalEntry->id,
                'reference' => $item->journalEntry->reference,
                'description' => $item->description ?? $item->journalEntry->description,
                'debit' => $item->debit_amount,
                'credit' => $item->credit_amount,
                'balance' => $runningBalance,
            ];

            $totalDebits = Money::add($totalDebits, $item->debit_amount);
            $totalCredits = Money::add($totalCredits, $item->credit_amount);
        }

        $closingBalance = $this->balanceService->getAccountBalance($account, $endDate);

        $this->setData([
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->accountType->name,
                'normal_balance' => $normalBalance,
            ],
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'opening_balance' => $openingBalance,
            'transactions' => $transactions,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'closing_balance' => $closingBalance,
            'net_change' => Money::sub($closingBalance, $openingBalance),
            'transaction_count' => count($transactions),
        ]);

        return $this;
    }
}
