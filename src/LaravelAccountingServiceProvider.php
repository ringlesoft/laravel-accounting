<?php

namespace RingleSoft\LaravelAccounting;

use Illuminate\Support\ServiceProvider;
use RingleSoft\LaravelAccounting\Commands\InstallCommand;

class LaravelAccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->alias(Facades\LaravelAccounting::class, 'LaravelAccounting');
    }

    public function boot(): void
    {

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'ringlesoft');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->publishItems();

        $this->mergeConfigFrom(
            __DIR__.'/../config/accounting.php', 'accounting'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }

    private function publishItems(): void
    {
        if (! function_exists('config_path') || ! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/accounting.php' => config_path('accounting.php'),
        ], 'accounting-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'accounting-migrations');
    }
}
