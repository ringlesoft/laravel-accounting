<?php

namespace RingleSoft\LaravelAccounting\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RingleSoft\LaravelAccounting\Models\Report;
use RingleSoft\LaravelAccounting\Reports\AccountLedger;
use RingleSoft\LaravelAccounting\Reports\BalanceSheet;
use RingleSoft\LaravelAccounting\Reports\CashFlowStatement;
use RingleSoft\LaravelAccounting\Reports\GeneralLedger;
use RingleSoft\LaravelAccounting\Reports\IncomeStatement;
use RingleSoft\LaravelAccounting\Reports\TrialBalance;

class ReportService
{

    protected array $reports = [
        'balance_sheet' => BalanceSheet::class,
        'income_statement' => IncomeStatement::class,
        'cash_flow_statement' => CashFlowStatement::class,
        'trial_balance' => TrialBalance::class,
        'general_ledger' => GeneralLedger::class,
        'account_ledger' => AccountLedger::class,
    ];

    public function __construct(protected BalanceService $balanceService)
    {
    }

    /**
     * Register a custom report type
     */
    public function register(string $key, string $reportClass): self
    {
        if (!is_subclass_of($reportClass, Report::class)) {
            throw new InvalidArgumentException("Report class must extend " . Report::class);
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
     */
    public function generate(string $reportType, array $params = []): Report
    {
        $cacheKey = $this->getCacheKey($reportType, $params);
        $cacheTtl = config('laravel_accounting.report_cache_ttl', 3600);

        if (config('laravel_accounting.report_cache_enabled', true)) {
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
     */
    protected function createReportInstance(string $reportType): Report
    {
        if (!$this->hasReport($reportType)) {
            throw new InvalidArgumentException("Unknown report type: {$reportType}");
        }

        $reportClass = $this->reports[$reportType];

        return new $reportClass($this->balanceService);
    }

    /**
     * Generate cache key for report
     */
    protected function getCacheKey(string $reportType, array $params): string
    {
        $paramsHash = md5(json_encode($params, JSON_THROW_ON_ERROR));
        return "laravel_accounting.report.{$reportType}.{$paramsHash}";
    }

    /**
     * Clear report cache
     */
    public function clearCache(?string $reportType = null): void
    {
        if ($reportType) {
            Cache::forget("laravel_accounting.report.{$reportType}.*");
        } else {
            Cache::flush();
        }
    }

    /**
     * Export report to specific format
     */
    public function export(Report $report, string $format = 'array'): mixed
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
            $csv .= $data['title'] . "\n\n";
        }

        if (is_array($data['rows'])) {
            if (!empty($data['rows'])) {
                $headers = array_keys($data['rows'][0]);
                $csv .= implode(',', $headers) . "\n";

                foreach ($data['rows'] as $row) {
                    $csv .= implode(',', array_map(function ($value) {
                            return is_numeric($value) ? $value : '"' . str_replace('"', '""', $value) . '"';
                        }, array_values($row))) . "\n";
                }
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
        ];
    }

    /**
     * @param string $reportType
     * @param array $params
     * @return Report
     */
    public function __call(string $reportType, array $params = []): Report
    {
        $snakeCaseType = Str::of($reportType)->snake()->toString();
        if (isset($this->reports[$snakeCaseType])) {
            $reportClass = $this->reports[$snakeCaseType];
            return (new $reportClass(params: $params[0]));
        }
        // Todo: if the method isn't in this class throw error
        if (!method_exists($this, $reportType)) {
            throw new InvalidArgumentException("Report type {$reportType} does not exist");
        }
        throw new InvalidArgumentException("Report type {$reportType} does not exist");
    }

}

