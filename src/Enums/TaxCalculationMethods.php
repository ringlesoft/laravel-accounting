<?php

namespace RingleSoft\LaravelAccounting\Enums;

enum TaxCalculationMethods: string
{
    case INCLUSIVE = 'Inclusive';
    case EXCLUSIVE = 'Exclusive';
}
