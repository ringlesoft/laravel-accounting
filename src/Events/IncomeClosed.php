<?php

namespace RingleSoft\LaravelAccounting\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;

class IncomeClosed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public FiscalPeriod $period,
        public float $netIncome,
        public ?int $userId = null
    ) {}
}
