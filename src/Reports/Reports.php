<?php

namespace RingleSoft\LaravelAccounting\Reports;

use RingleSoft\LaravelAccounting\Models\Report;

class Reports
{
    //Statement of Financial Position (Balance Sheet).
    //Statement of Profit or Loss and Other Comprehensive Income (Income Statement).
    //Statement of Cash Flows.
    //Statement of Changes in Equity.
    //Trial Balance, General Ledger, Account Ledgers.
    //Notes/disclosures (configurable).
    public array $reports = [
        'balanceSheet' => BalanceSheet::class,
        'incomeStatement' => IncomeStatement::class,
        'cashFlowStatement' => CashFlowStatement::class,
        'trialBalance' => TrialBalance::class,
        'generalLedger' => GeneralLedger::class,
        'accountLedgers' => AccountLedgers::class,
    ];


    public function register(string $key, string $class)
    {
        // TODO check validity of the class
        $this->reports[$key] = $class;
    }

    public function __invoke()
    {
        // TODO: check if report exists
        // TODO: return the report instance
    }


    //Invoke

}
