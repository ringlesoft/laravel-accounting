<?php

namespace RingleSoft\LaravelAccounting\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;
use RingleSoft\LaravelAccounting\Services\ClosingService;
use RingleSoft\LaravelAccounting\Services\PeriodService;
use Throwable;

/**
 * Posts the period-end closing entries (via ClosingService::commit()) and
 * then closes the fiscal period (via PeriodService::closePeriod()) for
 * every open period whose end date has already passed, or for one specific
 * period via --period.
 *
 * Designed to run unattended: it never prompts, processes every eligible
 * period even if one fails, and exits non-zero only if at least one did -
 * safe to wire into the app's scheduler (see the README's Fiscal Periods
 * section for a Schedule::command() example).
 */
class ClosePeriodCommand extends Command
{
    protected $signature = 'accounting:close-period
                            {--period= : Specific fiscal period ID to close (default: every open period whose end date has already passed)}
                            {--tenant= : Limit to fiscal periods belonging to this tenant_id (a console run is otherwise not scoped to any tenant - see the Multi-Tenancy FAQ in the README)}
                            {--lock : Also lock the period immediately after it closes, preventing it from ever being reopened}
                            {--dry-run : Preview the closing entries and resulting net income for each period without posting or closing anything}
                            {--user= : User ID to attribute the closing entries and period closure to (defaults to none, i.e. a system action)}';

    protected $description = 'Post period-end closing entries and close every fiscal period whose end date has passed';

    public function __construct(
        protected ClosingService $closingService,
        protected PeriodService $periodService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $periodOption = $this->option('period');
        $dryRun = (bool) $this->option('dry-run');
        $lock = (bool) $this->option('lock');
        $userId = $this->option('user') !== null ? (int) $this->option('user') : null;

        $periods = $periodOption !== null
            ? $this->findExplicitPeriod($periodOption)
            : $this->findDuePeriods($this->option('tenant'));

        if ($periods === null) {
            return self::FAILURE;
        }

        if ($periods->isEmpty()) {
            $this->info('No fiscal periods are due to close.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '').'Processing '.$periods->count().' fiscal period(s)...');
        $this->newLine();

        $failures = 0;

        foreach ($periods as $period) {
            if (! $this->processPeriod($period, $dryRun, $lock, $userId)) {
                $failures++;
            }
            $this->newLine();
        }

        if ($failures > 0) {
            $this->error("Completed with {$failures} failure(s) - see above for details.");

            return self::FAILURE;
        }

        $this->info($dryRun ? '✓ Dry run completed.' : '✓ Period closing completed successfully!');

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, FiscalPeriod>|null null on a not-found error (already reported)
     */
    protected function findExplicitPeriod(int|string $periodId): ?Collection
    {
        $period = FiscalPeriod::find($periodId);

        if (! $period) {
            $this->error("Fiscal period with ID {$periodId} not found.");

            return null;
        }

        return collect([$period]);
    }

    /**
     * @return Collection<int, FiscalPeriod>
     */
    protected function findDuePeriods(int|string|null $tenantId): Collection
    {
        $query = FiscalPeriod::query()
            ->open()
            ->whereDate('end_date', '<', now()->toDateString())
            ->orderBy('end_date');

        if ($tenantId !== null) {
            $query->byTenant($tenantId);
        }

        return $query->get();
    }

    protected function processPeriod(FiscalPeriod $period, bool $dryRun, bool $lock, ?int $userId): bool
    {
        $label = "{$period->name} ({$period->start_date->toDateString()} to {$period->end_date->toDateString()})";
        if ($period->tenant_id) {
            $label .= " [tenant: {$period->tenant_id}]";
        }
        $this->line("Period: {$label}");

        if ($period->is_closed) {
            $this->warn('  Already closed - skipping.');

            return true;
        }

        try {
            $preview = $this->closingService->preview($period);

            $this->line(sprintf(
                '  Revenue: %s | Expenses: %s | Net income: %s | Dividends: %s',
                number_format($preview->totalRevenue, 2),
                number_format($preview->totalExpenses, 2),
                number_format($preview->netIncome, 2),
                number_format($preview->dividendsDeclared, 2),
            ));

            if ($dryRun) {
                $this->comment('  [DRY RUN] Nothing was posted or closed.');

                return true;
            }

            $this->closingService->commit($period, $userId);
            $this->periodService->closePeriod($period, $userId);

            if ($lock) {
                $this->periodService->lockPeriod($period, $userId);
            }

            $this->info('  ✓ Closed'.($lock ? ' and locked.' : '.'));

            return true;
        } catch (Throwable $e) {
            $this->error("  ✗ Failed: {$e->getMessage()}");

            return false;
        }
    }
}
