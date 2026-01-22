<?php

namespace RingleSoft\LaravelAccounting\Commands;

use Illuminate\Console\Command;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;
use RingleSoft\LaravelAccounting\Services\BalanceService;

class UpdateBalancesCommand extends Command
{
    protected $signature = 'accounting:update-balances 
                            {--period= : Fiscal period ID to update balances for}
                            {--account= : Specific account ID to update}
                            {--all : Update all periods and accounts}';

    protected $description = 'Recalculate and update cached account balances from journal entries';

    protected BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        parent::__construct();
        $this->balanceService = $balanceService;
    }

    public function handle(): int
    {
        $this->info('Starting balance update process...');
        $this->newLine();

        $periodId = $this->option('period');
        $accountId = $this->option('account');
        $updateAll = $this->option('all');

        if ($periodId) {
            return $this->updatePeriodBalances($periodId, $accountId);
        }

        if ($accountId) {
            return $this->updateAccountBalances($accountId);
        }

        if ($updateAll) {
            return $this->updateAllBalances();
        }

        $this->error('Please specify either --period, --account, or --all option');
        $this->newLine();
        $this->comment('Examples:');
        $this->line('  php artisan accounting:update-balances --period=1');
        $this->line('  php artisan accounting:update-balances --account=5');
        $this->line('  php artisan accounting:update-balances --period=1 --account=5');
        $this->line('  php artisan accounting:update-balances --all');

        return self::FAILURE;
    }

    protected function updatePeriodBalances(int|string $periodId, ?int $accountId = null): int
    {
        $period = FiscalPeriod::find($periodId);

        if (! $period) {
            $this->error("Fiscal period with ID {$periodId} not found.");

            return self::FAILURE;
        }

        $this->info("Updating balances for period: {$period->name}");
        $this->comment("Period: {$period->start_date->toDateString()} to {$period->end_date->toDateString()}");
        $this->newLine();

        if ($accountId) {
            $account = Account::find($accountId);
            if (! $account) {
                $this->error("Account with ID {$accountId} not found.");

                return self::FAILURE;
            }

            $this->updateSingleAccountBalance($account, $period);
        } else {
            $accounts = Account::where('is_active', true)->get();
            $this->updateAccountsForPeriod($accounts, $period);
        }

        $this->newLine();
        $this->info('✓ Balance update completed successfully!');

        return self::SUCCESS;
    }

    protected function updateAccountBalances(int|string $accountId): int
    {
        $account = Account::find($accountId);

        if (! $account) {
            $this->error("Account with ID {$accountId} not found.");

            return self::FAILURE;
        }

        $this->info("Updating balances for account: {$account->code} - {$account->name}");
        $this->newLine();

        $periods = FiscalPeriod::orderBy('start_date', 'asc')->get();

        if ($periods->isEmpty()) {
            $this->warn('No fiscal periods found.');

            return self::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($periods->count());
        $progressBar->setFormat('verbose');

        foreach ($periods as $period) {
            $this->updateSingleAccountBalance($account, $period);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info('✓ Balance update completed successfully!');

        return self::SUCCESS;
    }

    protected function updateAllBalances(): int
    {
        if (! $this->confirm('This will recalculate balances for all accounts and periods. Continue?', true)) {
            $this->comment('Operation cancelled.');

            return self::SUCCESS;
        }

        $this->info('Updating all account balances...');
        $this->newLine();

        $periods = FiscalPeriod::orderBy('start_date', 'asc')->get();
        $accounts = Account::where('is_active', true)->get();

        if ($periods->isEmpty()) {
            $this->warn('No fiscal periods found.');

            return self::SUCCESS;
        }

        if ($accounts->isEmpty()) {
            $this->warn('No active accounts found.');

            return self::SUCCESS;
        }

        $totalOperations = $periods->count() * $accounts->count();
        $this->comment("Processing {$periods->count()} periods × {$accounts->count()} accounts = {$totalOperations} balance records");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($periods->count());
        $progressBar->setFormat('verbose');

        $updatedCount = 0;

        foreach ($periods as $period) {
            $count = $this->updateAccountsForPeriod($accounts, $period, false);
            $updatedCount += $count;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info("✓ Updated {$updatedCount} balance records successfully!");

        return self::SUCCESS;
    }

    protected function updateAccountsForPeriod($accounts, FiscalPeriod $period, bool $showProgress = true): int
    {
        $progressBar = null;
        if ($showProgress) {
            $progressBar = $this->output->createProgressBar($accounts->count());
            $progressBar->setFormat('verbose');
        }

        $updatedCount = 0;

        foreach ($accounts as $account) {
            $this->updateSingleAccountBalance($account, $period);
            $updatedCount++;

            if ($progressBar) {
                $progressBar->advance();
            }
        }

        if ($progressBar) {
            $progressBar->finish();
            $this->newLine();
        }

        return $updatedCount;
    }

    protected function updateSingleAccountBalance(Account $account, FiscalPeriod $period): void
    {
        $this->balanceService->updateAccountBalance($account, $period);
    }
}
