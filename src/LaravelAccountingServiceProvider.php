<?php

namespace RingleSoft\LaravelAccounting;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RingleSoft\LaravelAccounting\Commands\ClosePeriodCommand;
use RingleSoft\LaravelAccounting\Commands\InstallCommand;
use RingleSoft\LaravelAccounting\Commands\UpdateBalancesCommand;
use RingleSoft\LaravelAccounting\Events\JournalEntryPosted;
use RingleSoft\LaravelAccounting\Events\JournalEntryReversed;
use RingleSoft\LaravelAccounting\Events\JournalEntryVoided;
use RingleSoft\LaravelAccounting\Listeners\InvalidateAccountingCaches;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Observers\AccountObserver;
use RingleSoft\LaravelAccounting\Observers\FiscalPeriodObserver;
use RingleSoft\LaravelAccounting\Observers\JournalEntryObserver;

class LaravelAccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->alias(Facades\LaravelAccounting::class, 'LaravelAccounting');
    }

    public function boot(): void
    {
        $this->registerObservers();
        $this->registerEventListeners();

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'ringlesoft');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->publishItems();

        $this->mergeConfigFrom(
            __DIR__.'/../config/laravel_accounting.php', 'laravel_accounting'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                UpdateBalancesCommand::class,
                ClosePeriodCommand::class,
            ]);
        }
    }

    private function publishItems(): void
    {
        if (! function_exists('config_path') || ! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/laravel_accounting.php' => config_path('laravel_accounting.php'),
        ], 'accounting-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'accounting-migrations');

        $this->publishes([
            __DIR__.'/../database/seeders' => database_path('seeders'),
        ], 'accounting-seeders');
    }

    private function registerObservers(): void
    {
        JournalEntry::observe(JournalEntryObserver::class);
        Account::observe(AccountObserver::class);
        FiscalPeriod::observe(FiscalPeriodObserver::class);
    }

    private function registerEventListeners(): void
    {
        Event::listen(JournalEntryPosted::class, [InvalidateAccountingCaches::class, 'handlePosted']);
        Event::listen(JournalEntryReversed::class, [InvalidateAccountingCaches::class, 'handleReversed']);
        Event::listen(JournalEntryVoided::class, [InvalidateAccountingCaches::class, 'handleVoided']);
    }
}
