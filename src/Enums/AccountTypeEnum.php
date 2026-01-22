<?php

namespace RingleSoft\LaravelAccounting\Enums;

use RingleSoft\LaravelAccounting\Models\AccountType;

enum AccountTypeEnum: string
{
    case ASSET = 'Asset';
    case LIABILITY = 'Liability';
    case EQUITY = 'Equity';
    case INCOME = 'Income';
    case EXPENSE = 'Expense';


    public function accountType(): ?AccountType
    {
        return AccountType::findByName($this->value);
    }

    public function getId(): ?int
    {
        return $this->accountType()?->id;
    }
}
