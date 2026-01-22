<?php

namespace RingleSoft\LaravelAccounting\Reports;

use Carbon\Carbon;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\Report;
use RingleSoft\LaravelAccounting\Services\AccountService;

class ChartsOfAccounts extends Report
{
    protected AccountService $accountService;

    public function __construct(?array $params = null)
    {
        parent::__construct($params);
        $this->accountService = app(AccountService::class);
    }

    public function generate(): self
    {
        $this->setTitle('Chart of Accounts');

        $includeInactive = $this->getParameter('include_inactive', false);
        $accountTypeFilter = $this->getParameter('account_type');
        $showBalances = $this->getParameter('show_balances', false);
        $asOfDate = $this->getParameter('as_of_date', now());

        $accounts = $this->accountService->getChartOfAccounts();

        if (!$includeInactive) {
            $accounts = $accounts->map(function ($account) {
                return $this->filterInactiveAccounts($account);
            })->filter();
        }

        if ($accountTypeFilter) {
            $accounts = $accounts->filter(function ($account) use ($accountTypeFilter) {
                return $account['type'] === $accountTypeFilter;
            });
        }

        if ($showBalances) {
            $accounts = $accounts->map(function ($account) use ($asOfDate) {
                return $this->addBalancesToAccount($account, $asOfDate);
            });
        }

        $summary = $this->generateSummary($accounts);

        $this->setData([
            'accounts' => $accounts->values()->toArray(),
            'summary' => $summary,
            'include_inactive' => $includeInactive,
            'account_type_filter' => $accountTypeFilter,
            'show_balances' => $showBalances,
            'as_of_date' => is_string($asOfDate) ? $asOfDate : $asOfDate->toDateString(),
        ]);

        return $this;
    }

    /**
     * Filter out inactive accounts recursively
     */
    protected function filterInactiveAccounts(array $account): ?array
    {
        if (!$account['is_active']) {
            return null;
        }

        if (!empty($account['children'])) {
            $account['children'] = collect($account['children'])
                ->map(fn($child) => $this->filterInactiveAccounts($child))
                ->filter()
                ->values()
                ->toArray();
        }

        return $account;
    }

    /**
     * Add balance information to account recursively
     */
    protected function addBalancesToAccount(array $account, $asOfDate): array
    {
        $accountModel = Account::find($account['id']);

        if ($accountModel) {
            $balance = $this->balanceService->getAccountBalance(
                $accountModel,
                is_string($asOfDate) ? Carbon::parse($asOfDate) : $asOfDate
            );

            $account['balance'] = $balance;
            $account['balance_formatted'] = number_format(abs($balance), 2);
            $account['balance_type'] = $balance >= 0 ? 'normal' : 'abnormal';
        }

        if (!empty($account['children'])) {
            $account['children'] = collect($account['children'])
                ->map(fn($child) => $this->addBalancesToAccount($child, $asOfDate))
                ->toArray();
        }

        return $account;
    }

    /**
     * Generate summary statistics
     */
    protected function generateSummary($accounts): array
    {
        $totalAccounts = 0;
        $activeAccounts = 0;
        $systemAccounts = 0;
        $accountsByType = [];

        $countAccounts = static function ($accountList) use (&$countAccounts, &$totalAccounts, &$activeAccounts, &$systemAccounts, &$accountsByType) {
            foreach ($accountList as $account) {
                $totalAccounts++;

                if ($account['is_active']) {
                    $activeAccounts++;
                }

                if ($account['is_system_account']) {
                    $systemAccounts++;
                }

                $type = $account['type'];
                if (!isset($accountsByType[$type])) {
                    $accountsByType[$type] = 0;
                }
                $accountsByType[$type]++;

                if (!empty($account['children'])) {
                    $countAccounts($account['children']);
                }
            }
        };

        $countAccounts($accounts);

        return [
            'total_accounts' => $totalAccounts,
            'active_accounts' => $activeAccounts,
            'inactive_accounts' => $totalAccounts - $activeAccounts,
            'system_accounts' => $systemAccounts,
            'accounts_by_type' => $accountsByType,
        ];
    }
}
