<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelAccounting\Enums\AccountType as AccountTypeEnum;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Models\JournalEntryItem;
use RingleSoft\LaravelAccounting\Models\Report;

class CashFlowStatement extends Report
{
    protected string $accountsTable;
    protected string $journalEntryItemsTable;
    protected string $journalEntriesTable;

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

        $this->accountsTable = (new Account())->getTable();
        $this->journalEntryItemsTable = (new JournalEntryItem())->getTable();
        $this->journalEntriesTable = (new JournalEntry())->getTable();

        $netIncome = $this->calculateNetIncome($startDate, $endDate);

        $operating = $this->calculateOperatingActivities($startDate, $endDate, $netIncome);
        $investing = $this->calculateInvestingActivities($startDate, $endDate);
        $financing = $this->calculateFinancingActivities($startDate, $endDate);

        $netCashFlow = $operating['total'] + $investing['total'] + $financing['total'];

        $assetTypeId = AccountTypeEnum::ASSET->getId();
        $beginningCash = 0;
        $endingCash = 0;

        if ($assetTypeId) {
            $cashAccounts = DB::table("{$this->accountsTable} as acc")
                ->where('acc.account_type_id', $assetTypeId)
                ->where('acc.is_active', true)
                ->where('acc.code', 'LIKE', '%CASH%')
                ->orWhere('acc.name', 'LIKE', '%Cash%')
                ->select('acc.id')
                ->first();

            if ($cashAccounts) {
                $cashAccount = Account::find($cashAccounts->id);
                if ($cashAccount) {
                    $beginningCash = $this->balanceService->getAccountBalance($cashAccount, $startDate->copy()->subDay());
                    $endingCash = $this->balanceService->getAccountBalance($cashAccount, $endDate);
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
        $incomeTypeId = AccountTypeEnum::INCOME->getId();
        $expenseTypeId = AccountTypeEnum::EXPENSE->getId();

        if (!$incomeTypeId || !$expenseTypeId) {
            return 0;
        }

        $revenueData = $this->getAccountBalancesForPeriod($incomeTypeId, $startDate, $endDate);
        $expenseData = $this->getAccountBalancesForPeriod($expenseTypeId, $startDate, $endDate);

        $totalRevenue = 0;
        foreach ($revenueData as $account) {
            $totalRevenue += $account->total_credits - $account->total_debits;
        }

        $totalExpenses = 0;
        foreach ($expenseData as $account) {
            $totalExpenses += $account->total_debits - $account->total_credits;
        }

        return $totalRevenue - $totalExpenses;
    }

    protected function calculateOperatingActivities(Carbon $startDate, Carbon $endDate, float $netIncome): array
    {
        $adjustments = [];
        $total = $netIncome;

        $assetTypeId = AccountTypeEnum::ASSET->getId();
        $liabilityTypeId = AccountTypeEnum::LIABILITY->getId();

        if (!$assetTypeId || !$liabilityTypeId) {
            return [
                'net_income' => $netIncome,
                'adjustments' => $adjustments,
                'total' => $total,
            ];
        }

        $currentAssets = DB::table("{$this->accountsTable} as acc")
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

        $currentLiabilities = DB::table("{$this->accountsTable} as acc")
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

        if (!$assetTypeId) {
            return ['activities' => $activities, 'total' => $total];
        }

        $fixedAssets = $this->getAccountBalancesForPeriod($assetTypeId, $startDate, $endDate, '%FIXED%');

        foreach ($fixedAssets as $account) {
            $netChange = $account->total_debits - $account->total_credits;

            if (abs($netChange) > 0.01) {
                $activities[] = [
                    'description' => $netChange > 0 ? "Purchase of {$account->name}" : "Sale of {$account->name}",
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

        if (!$liabilityTypeId || !$equityTypeId) {
            return ['activities' => $activities, 'total' => $total];
        }

        $longTermLiabilities = $this->getAccountBalancesForPeriod($liabilityTypeId, $startDate, $endDate, '%LONG%');

        foreach ($longTermLiabilities as $account) {
            $netChange = $account->total_credits - $account->total_debits;

            if (abs($netChange) > 0.01) {
                $activities[] = [
                    'description' => $netChange > 0 ? "Proceeds from {$account->name}" : "Repayment of {$account->name}",
                    'amount' => $netChange,
                ];
                $total += $netChange;
            }
        }

        $equityAccounts = $this->getAccountBalancesForPeriod($equityTypeId, $startDate, $endDate);

        foreach ($equityAccounts as $account) {
            $netChange = $account->total_credits - $account->total_debits;

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

    /**
     * Get account balances for a specific account type within a period
     */
    protected function getAccountBalancesForPeriod(int $accountTypeId, Carbon $startDate, Carbon $endDate, ?string $codeFilter = null)
    {
        $query = DB::table("{$this->accountsTable} as acc")
            ->leftJoin("{$this->journalEntryItemsTable} as jei", 'acc.id', '=', 'jei.account_id')
            ->leftJoin("{$this->journalEntriesTable} as je", function ($join) use ($startDate, $endDate) {
                $join->on('jei.journal_entry_id', '=', 'je.id')
                    ->where('je.is_posted', '=', true)
                    ->where('je.is_voided', '=', false)
                    ->whereBetween('je.transaction_date', [$startDate, $endDate]);
            })
            ->where('acc.is_active', true)
            ->where('acc.account_type_id', $accountTypeId);

        if ($codeFilter) {
            $query->where('acc.code', 'LIKE', $codeFilter);
        }

        return $query
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
