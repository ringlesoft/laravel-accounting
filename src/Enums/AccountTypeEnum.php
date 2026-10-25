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

    /**
     * The account type's key: an int, or a UUID string when `use_uuid` is on.
     */
    public function getId(): int|string|null
    {
        return $this->accountType()?->getKey();
    }
}
