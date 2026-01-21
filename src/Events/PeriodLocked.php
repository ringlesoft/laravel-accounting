<?php

namespace RingleSoft\LaravelAccounting\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;

class PeriodLocked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public FiscalPeriod $period,
        public ?int $userId = null
    ) {
    }
}
