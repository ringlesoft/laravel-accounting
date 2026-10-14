<?php

namespace RingleSoft\LaravelAccounting\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use RingleSoft\LaravelAccounting\Models\Report;
use RingleSoft\LaravelAccounting\Reports\AccountLedger;
use RingleSoft\LaravelAccounting\Reports\BalanceSheet;
use RingleSoft\LaravelAccounting\Reports\CashFlowStatement;
use RingleSoft\LaravelAccounting\Reports\ChartsOfAccounts;
use RingleSoft\LaravelAccounting\Reports\GeneralLedger;
use RingleSoft\LaravelAccounting\Reports\IncomeStatement;
use RingleSoft\LaravelAccounting\Reports\TrialBalance;

/**
 * @property Report $balanceSheet
 * @property Report $incomeStatement
 * @property Report $cashFlowStatement
 * @property Report $trialBalance
 * @property Report $generalLedger
 * @property Report $accountLedger
 * @property Report $chartOfAccounts
 */
class ReportService
{
    protected array $reports = [
        'balance_sheet' => BalanceSheet::class,
        'income_statement' => IncomeStatement::class,
        'cash_flow_statement' => CashFlowStatement::class,
        'trial_balance' => TrialBalance::class,
        'general_ledger' => GeneralLedger::class,
        'account_ledger' => AccountLedger::class,
        'charts_of_accounts' => ChartsOfAccounts::class,
    ];

    public function __construct(protected BalanceService $balanceService) {}

    /**
     * Register a custom report type
     *
     * @throws InvalidArgumentException
     */
    public function register(string $key, string $reportClass): self
    {
        if (! is_subclass_of($reportClass, Report::class)) {
            throw new InvalidArgumentException('Report class must extend '.Report::class);
        }
        $this->reports[$key] = $reportClass;

        return $this;
    }

    /**
     * Check if a report type exists
     */
    public function hasReport(string $reportType): bool
    {
        return isset($this->reports[$reportType]);
    }

    /**
     * Get all registered report types
     */
    public function getRegisteredReports(): array
    {
        return array_keys($this->reports);
    }

    /**
     * Generate a report
     *
     * @throws JsonException
     */
    public function generate(string $reportType, array $params = []): Report
    {
        $cacheKey = $this->getCacheKey($reportType, $params);
        $cacheTtl = config('laravel_accounting.report_cache_ttl', 3600);

        if (config('laravel_accounting.report_cache_enabled', true)) {
            $this->rememberCacheKey($reportType, $cacheKey, $cacheTtl);

            return Cache::remember($cacheKey, $cacheTtl, function () use ($reportType, $params) {
                return $this->generateReport($reportType, $params);
            });
        }

        return $this->generateReport($reportType, $params);
    }

    /**
     * Generate report without caching
     */
    protected function generateReport(string $reportType, array $params): Report
    {
        $report = $this->createReportInstance($reportType);

        $report->setParameters($params);
        $report->generate();

        return $report;
    }

    /**
     * Create report instance based on type
     *
     * @throws InvalidArgumentException
     */
    protected function createReportInstance(string $reportType): Report
    {
        if (! $this->hasReport($reportType)) {
            throw new InvalidArgumentException("Unknown report type: {$reportType}");
        }

        $reportClass = $this->reports[$reportType];

        return new $reportClass(null);
    }

    /**
     * The package-specific prefix used for all of this service's cache entries.
     */
    protected function cachePrefix(): string
    {
        return config('laravel_accounting.cache_prefix', 'laravel_accounting');
    }

    /**
     * Generate cache key for report
     *
     * @throws JsonException
     */
    protected function getCacheKey(string $reportType, array $params): string
    {
        $paramsHash = md5(json_encode($params, JSON_THROW_ON_ERROR));

        return $this->cachePrefix().".report.$reportType.$paramsHash";
    }

    /**
     * Key of the per-report-type index listing every cache key generated for it,
     * so clearCache() can forget exactly those keys instead of relying on
     * wildcard forget() (unsupported by most cache stores) or a global flush.
     */
    protected function getCacheIndexKey(string $reportType): string
    {
        return $this->cachePrefix().".report_cache_index.$reportType";
    }

    /**
     * Track a generated report cache key in its report type's index.
     */
    protected function rememberCacheKey(string $reportType, string $cacheKey, int $ttl): void
    {
        $indexKey = $this->getCacheIndexKey($reportType);
        $keys = Cache::get($indexKey, []);

        if (! in_array($cacheKey, $keys, true)) {
            $keys[] = $cacheKey;
            Cache::put($indexKey, $keys, $ttl);
        }
    }

    /**
     * Clear this package's report cache, scoped by its own prefix.
     * Passing a report type clears only that report's cached results;
     * omitting it clears every report type this service knows about.
     */
    public function clearCache(?string $reportType = null): void
    {
        $reportTypes = $reportType ? [$reportType] : array_keys($this->reports);

        foreach ($reportTypes as $type) {
            $indexKey = $this->getCacheIndexKey($type);

            foreach (Cache::get($indexKey, []) as $cacheKey) {
                Cache::forget($cacheKey);
            }

            Cache::forget($indexKey);
        }
    }

    /**
     * Export report to specific format
     *
     * @throws JsonException
     * @throws InvalidArgumentException Unsupported $format
     */
    public function export(Report $report, string $format = 'array'): string|array|bool
    {
        return match ($format) {
            'array' => $report->toArray(),
            'json' => json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
            'csv' => $this->exportToCsv($report),
            default => throw new InvalidArgumentException("Unsupported export format: {$format}"),
        };
    }

    /**
     * Export report to CSV format
     */
    protected function exportToCsv(Report $report): string
    {
        $data = $report->toArray();
        $csv = '';

        if (isset($data['title'])) {
            $csv .= $data['title']."\n\n";
        }

        if (is_array($data['rows']) && ! empty($data['rows'])) {
            $headers = array_keys($data['rows'][0]);
            $csv .= implode(',', $headers)."\n";

            foreach ($data['rows'] as $row) {
                $csv .= implode(',', array_map(static function ($value) {
                    return is_numeric($value) ? $value : '"'.str_replace('"', '""', $value).'"';
                }, array_values($row)))."\n";
            }
        }

        return $csv;
    }

    /**
     * Get available report types
     */
    public function getAvailableReports(): array
    {
        return [
            'balance_sheet' => [
                'name' => 'Balance Sheet',
                'description' => 'Statement of financial position showing assets, liabilities, and equity',
                'parameters' => ['as_of_date', 'tenant_id'],
            ],
            'income_statement' => [
                'name' => 'Income Statement',
                'description' => 'Statement of profit and loss showing revenues and expenses',
                'parameters' => ['start_date', 'end_date', 'tenant_id'],
            ],
            'cash_flow_statement' => [
                'name' => 'Cash Flow Statement',
                'description' => 'Statement of cash flows using indirect method',
                'parameters' => ['start_date', 'end_date', 'tenant_id'],
            ],
            'trial_balance' => [
                'name' => 'Trial Balance',
                'description' => 'List of all accounts with their debit and credit balances',
                'parameters' => ['as_of_date', 'tenant_id'],
            ],
            'general_ledger' => [
                'name' => 'General Ledger',
                'description' => 'Chronological list of all journal entries with running balances',
                'parameters' => ['start_date', 'end_date', 'account_id', 'tenant_id'],
            ],
            'account_ledger' => [
                'name' => 'Account Ledger',
                'description' => 'Detailed transaction history for a specific account',
                'parameters' => ['account_id', 'start_date', 'end_date', 'tenant_id'],
            ],
            'charts_of_accounts' => [
                'name' => 'Chart of Accounts',
                'description' => 'Hierarchical list of all accounts with optional balances',
                'parameters' => ['include_inactive', 'account_type', 'show_balances', 'as_of_date', 'tenant_id'],
            ],
        ];
    }

    /**
     * Magic dispatch for the camelCase report-type shortcuts shown in the
     * README (`trialBalance()`, `incomeStatement()`, `accountLedger()`, ...).
     * Resolves the called method name to its snake_case registered key and
     * instantiates that report class with the first argument as its params.
     *
     * @example
     * LaravelAccounting::reports()->trialBalance(['as_of_date' => now()])->generate()->getData();
     *
     */
    public function __call(string $reportType, array $params = []): Report
    {
        $snakeCaseType = Str::of($reportType)->snake()->toString();
        if (isset($this->reports[$snakeCaseType])) {
            $reportClass = $this->reports[$snakeCaseType];

            return new $reportClass(params: $params[0]);
        }
        // Todo: if the method isn't in this class throw error
        if (! method_exists($this, $reportType)) {
            throw new InvalidArgumentException("Report type {$reportType} does not exist");
        }
        throw new InvalidArgumentException("Report type {$reportType} does not exist");
    }
}
