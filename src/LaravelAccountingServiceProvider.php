<?php

namespace RingleSoft\LaravelAccounting;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use RingleSoft\LaravelAccounting\Commands\LaravelAccountingCommand;

class LaravelAccountingServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-accounting')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-accounting_table')
            ->hasCommand(LaravelAccountingCommand::class);
    }
}
