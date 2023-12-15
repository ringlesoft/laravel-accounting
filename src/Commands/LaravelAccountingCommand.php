<?php

namespace RingleSoft\LaravelAccounting\Commands;

use Illuminate\Console\Command;

class LaravelAccountingCommand extends Command
{
    public $signature = 'laravel-accounting';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
