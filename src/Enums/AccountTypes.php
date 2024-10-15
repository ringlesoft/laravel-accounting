<?php

namespace RingleSoft\LaravelAccounting\Enums;

enum AccountTypes: string
{
    case ASSET = 'Asset';
    case LIABILITY = 'Liability';
    case EQUITY = 'Equity';
    case INCOME = 'Income';
    case EXPENSE = 'Expense';
}
