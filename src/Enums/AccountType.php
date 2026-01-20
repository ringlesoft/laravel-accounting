<?php

namespace RingleSoft\LaravelAccounting\Enums;

enum AccountType: string
{
    case ASSET = 'Asset';
    case LIABILITY = 'Liability';
    case EQUITY = 'Equity';
    case INCOME = 'Income';
    case EXPENSE = 'Expense';
}
