<?php

namespace RingleSoft\LaravelAccounting\Enums;

enum NormalBalance: string
{
    // Lowercase to match the account_types migration's enum('normal_balance', ['debit', 'credit'])
    // constraint and the literal 'debit'/'credit' comparisons used throughout the reports.
    case DEBIT = 'debit';
    case CREDIT = 'credit';
}
