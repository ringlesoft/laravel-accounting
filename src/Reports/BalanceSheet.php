<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelAccounting\Enums\AccountType as AccountTypeEnum;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Models\JournalEntryItem;
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

        $assetTypeId = AccountTypeEnum::ASSET->getId();
        $liabilityTypeId = AccountTypeEnum::LIABILITY->getId();
        $equityTypeId = AccountTypeEnum::EQUITY->getId();

        if (!$assetTypeId || !$liabilityTypeId || !$equityTypeId) {
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

        $accountsTable = (new Account())->getTable();
        $journalEntryItemsTable = (new JournalEntryItem())->getTable();
        $journalEntriesTable = (new JournalEntry())->getTable();

        $assetAccounts = $this->getAccountBalances($accountsTable, $journalEntryItemsTable, $journalEntriesTable, $assetTypeId, $asOfDate);
        $liabilityAccounts = $this->getAccountBalances($accountsTable, $journalEntryItemsTable, $journalEntriesTable, $liabilityTypeId, $asOfDate);
        $equityAccounts = $this->getAccountBalances($accountsTable, $journalEntryItemsTable, $journalEntriesTable, $equityTypeId, $asOfDate);

        $assets = [];
        $totalAssets = 0;

        foreach ($assetAccounts as $account) {
            $balance = $account->total_debits - $account->total_credits;

            if (abs($balance) < 0.01) {
                continue;
            }

            $assets[] = [
                'account_id' => $account->account_id,
                'code' => $account->code,
                'name' => $account->name,
                'balance' => (float) $balance,
            ];
            $totalAssets += $balance;
        }

        $liabilities = [];
        $totalLiabilities = 0;

        foreach ($liabilityAccounts as $account) {
            $balance = $account->total_credits - $account->total_debits;

            if (abs($balance) < 0.01) {
                continue;
            }

            $liabilities[] = [
                'account_id' => $account->account_id,
                'code' => $account->code,
                'name' => $account->name,
                'balance' => (float) $balance,
            ];
            $totalLiabilities += $balance;
        }

        $equity = [];
        $totalEquity = 0;

        foreach ($equityAccounts as $account) {
            $balance = $account->total_credits - $account->total_debits;

            if (abs($balance) < 0.01) {
                continue;
            }

            $equity[] = [
                'account_id' => $account->account_id,
                'code' => $account->code,
                'name' => $account->name,
                'balance' => (float) $balance,
            ];
            $totalEquity += $balance;
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

    /**
     * Get account balances for a specific account type as of a date
     */
    protected function getAccountBalances(string $accountsTable, string $journalEntryItemsTable, string $journalEntriesTable, int $accountTypeId, Carbon $asOfDate)
    {
        return DB::table("{$accountsTable} as acc")
            ->leftJoin("{$journalEntryItemsTable} as jei", 'acc.id', '=', 'jei.account_id')
            ->leftJoin("{$journalEntriesTable} as je", function ($join) use ($asOfDate) {
                $join->on('jei.journal_entry_id', '=', 'je.id')
                    ->where('je.is_posted', '=', true)
                    ->where('je.is_voided', '=', false)
                    ->where('je.transaction_date', '<=', $asOfDate);
            })
            ->where('acc.is_active', true)
            ->where('acc.account_type_id', $accountTypeId)
            ->select(
                'acc.id as account_id',
                'acc.code',
                'acc.name',
                DB::raw('COALESCE(SUM(jei.debit_amount), 0) as total_debits'),
                DB::raw('COALESCE(SUM(jei.credit_amount), 0) as total_credits')
            )
            ->groupBy('acc.id', 'acc.code', 'acc.name')
            ->get();
    }
}
