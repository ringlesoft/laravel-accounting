<?php

namespace RingleSoft\LaravelAccounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;
use RingleSoft\LaravelAccounting\Reports\BalanceSheet;
use RingleSoft\LaravelAccounting\Reports\IncomeStatement;
use RingleSoft\LaravelAccounting\Reports\Report;
use RingleSoft\LaravelAccounting\Reports\TrialBalance;

class ReportService
{
    protected BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
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
        return match ($reportType) {
            'balance_sheet' => new BalanceSheet($this->balanceService),
            'income_statement' => new IncomeStatement($this->balanceService),
            'trial_balance' => new TrialBalance($this->balanceService),
            default => throw new \InvalidArgumentException("Unknown report type: {$reportType}"),
        };
    }

    /**
     * Generate cache key for report
     */
    protected function getCacheKey(string $reportType, array $params): string
    {
        $paramsHash = md5(json_encode($params));
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
     * Generate Balance Sheet
     */
    public function balanceSheet(?Carbon $asOfDate = null, ?int $tenantId = null): array
    {
        return $this->generate('balance_sheet', [
            'as_of_date' => $asOfDate ?? now(),
            'tenant_id' => $tenantId,
        ])->toArray();
    }

    /**
     * Generate Income Statement
     */
    public function incomeStatement(?Carbon $startDate = null, ?Carbon $endDate = null, ?int $tenantId = null): array
    {
        return $this->generate('income_statement', [
            'start_date' => $startDate ?? now()->startOfMonth(),
            'end_date' => $endDate ?? now()->endOfMonth(),
            'tenant_id' => $tenantId,
        ])->toArray();
    }

    /**
     * Generate Trial Balance
     */
    public function trialBalance(?FiscalPeriod $period = null, ?int $tenantId = null): array
    {
        return $this->generate('trial_balance', [
            'period' => $period,
            'tenant_id' => $tenantId,
        ])->toArray();
    }

    /**
     * Export report to specific format
     */
    public function export(Report $report, string $format = 'array'): mixed
    {
        return match ($format) {
            'array' => $report->toArray(),
            'json' => json_encode($report->toArray(), JSON_PRETTY_PRINT),
            'csv' => $this->exportToCsv($report),
            default => throw new \InvalidArgumentException("Unsupported export format: {$format}"),
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

        if (isset($data['rows']) && is_array($data['rows'])) {
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
            'trial_balance' => [
                'name' => 'Trial Balance',
                'description' => 'List of all accounts with their debit and credit balances',
                'parameters' => ['period', 'tenant_id'],
            ],
        ];
    }
}
