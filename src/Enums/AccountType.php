<?php

namespace RingleSoft\LaravelAccounting\Enums;

use RingleSoft\LaravelAccounting\Models\AccountType as AccountTypeModel;

enum AccountType: string
{
    case ASSET = 'Asset';
    case LIABILITY = 'Liability';
    case EQUITY = 'Equity';
    case INCOME = 'Income';
    case EXPENSE = 'Expense';


    public function getId(): ?int
    {
        // TODO introduce Cache
        return AccountTypeModel::findByName($this->value)?->id;
    }
}
