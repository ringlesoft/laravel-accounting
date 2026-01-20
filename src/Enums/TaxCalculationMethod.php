<?php

namespace RingleSoft\LaravelAccounting\Enums;

enum TaxCalculationMethod: string
{
    case INCLUSIVE = 'Inclusive';
    case EXCLUSIVE = 'Exclusive';
}
