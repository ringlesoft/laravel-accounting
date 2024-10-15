<?php

namespace RingleSoft\LaravelAccounting\Reports;

use DateTime;
use Illuminate\Support\Carbon;

class Report
{
    public Carbon $startDate;

    public Carbon $endDate;

    public function __construct(string|DateTime|Carbon|null $startDate, string|DateTime|Carbon|null $endDate)
    {
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
    }

    public function array(): array
    {
        return [];
    }
}
