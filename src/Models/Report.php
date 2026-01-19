<?php

namespace RingleSoft\LaravelAccounting\Models;

use Illuminate\Support\Carbon;

class Report
{
    protected string $name;
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected string $currencyCode;

//->asOf('2024-01-20')
//->from('2024-01-01')
//->to('2024-12-31')
//->inCurrency('USD')
//->generate();
//->compare();

}
