<?php

namespace RingleSoft\LaravelAccounting\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    public $signature = 'laravel-accounting:install';

    public $description = 'Install Laravel Accounting by Seeding initial data';

    public function handle(): int
    {
        $this->comment('Installing Laravel Accounting');

        return self::SUCCESS;
    }
}
