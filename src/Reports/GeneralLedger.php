<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Models\Report;

class GeneralLedger extends Report
{
    public function generate(): self
    {
        $startDate = $this->getParameter('start_date', now()->startOfMonth());
        $endDate = $this->getParameter('end_date', now()->endOfMonth());
        $accountId = $this->getParameter('account_id');

        if (is_string($startDate)) {
            $startDate = Carbon::parse($startDate);
        }
        if (is_string($endDate)) {
            $endDate = Carbon::parse($endDate);
        }

        $this->setTitle('General Ledger');

        $query = JournalEntry::with(['items.account.accountType'])
            ->where('is_posted', true)
            ->where('is_voided', false)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc');

        if ($accountId) {
            $query->whereHas('items', function ($q) use ($accountId) {
                $q->where('account_id', $accountId);
            });
        }

        $entries = $query->get();

        $ledgerEntries = [];
        $accountBalances = [];

        foreach ($entries as $entry) {
            foreach ($entry->items as $item) {
                if ($accountId && $item->account_id != $accountId) {
                    continue;
                }

                $accountCode = $item->account->code;

                if (!isset($accountBalances[$accountCode])) {
                    $accountBalances[$accountCode] = [
                        'account_id' => $item->account_id,
                        'code' => $accountCode,
                        'name' => $item->account->name,
                        'opening_balance' => $this->balanceService->getAccountBalance(
                            $item->account,
                            $startDate->copy()->subDay()
                        ),
                        'running_balance' => $this->balanceService->getAccountBalance(
                            $item->account,
                            $startDate->copy()->subDay()
                        ),
                    ];
                }

                $normalBalance = $item->account->accountType->normal_balance;
                $balanceChange = 0;

                if ($normalBalance === 'debit') {
                    $balanceChange = $item->debit_amount - $item->credit_amount;
                } else {
                    $balanceChange = $item->credit_amount - $item->debit_amount;
                }

                $accountBalances[$accountCode]['running_balance'] += $balanceChange;

                $ledgerEntries[] = [
                    'date' => $entry->transaction_date->toDateString(),
                    'entry_id' => $entry->id,
                    'reference' => $entry->reference,
                    'description' => $entry->description,
                    'account_code' => $accountCode,
                    'account_name' => $item->account->name,
                    'debit' => $item->debit_amount,
                    'credit' => $item->credit_amount,
                    'running_balance' => $accountBalances[$accountCode]['running_balance'],
                ];
            }
        }

        $this->setData([
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'account_filter' => $accountId,
            'entries' => $ledgerEntries,
            'account_summaries' => array_values($accountBalances),
            'total_entries' => count($ledgerEntries),
        ]);

        return $this;
    }
}
