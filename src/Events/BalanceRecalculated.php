<?php

namespace RingleSoft\LaravelAccounting\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;

class BalanceRecalculated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public FiscalPeriod $period,
        public int $accountsUpdated = 0,
        public ?int $userId = null
    ) {}
}
