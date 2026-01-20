<?php

namespace RingleSoft\LaravelAccounting;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Reports\Reports;
use RingleSoft\LaravelAccounting\Services\AccountService;
use RingleSoft\LaravelAccounting\Services\BalanceService;
use RingleSoft\LaravelAccounting\Services\JournalService;
use RingleSoft\LaravelAccounting\Services\PostingService;
use RingleSoft\LaravelAccounting\Services\ReportService;
use RingleSoft\LaravelAccounting\Templates\TemplateRegistry;
use Throwable;

class LaravelAccounting
{

    /**
     * Account Service
     * @return AccountService
     */
    public function accounts(): AccountService
    {
        return app(AccountService::class);
    }

    /**
     * @return BalanceService
     */
    public function balances(): BalanceService
    {
        return app(BalanceService::class);
    }

    /**
     * @return JournalService
     */
    public function journals(): JournalService
    {
        return app(JournalService::class);
    }

    /**
     * @return ReportService
     */
    public function reports(): ReportService
    {
        return app(ReportService::class);
    }

    public function posting(): PostingService
    {
        return app(PostingService::class);
    }

    public function templates()
    {
      return app(TemplateRegistry::class);
    }



    // - Journal creation and posting
    // - Reversals and adjustments
    // - Period validation and locking
    // - Balance and ledger queries
    // - Report data generation

    public function chartsOfAccounts(): Collection
    {
        return Account::all();
    }

    public function postModel(VoucherData $voucherData) {

    }

    public function postRaw(VoucherData $voucher) {}

    /**
     * @param JournalEntry $journalEntry
     * @return JournalEntry
     * @throws Throwable
     */
    public function reverse(JournalEntry $journalEntry): JournalEntry
    {
        return $this->journals()->reverse($journalEntry);
    }


}
