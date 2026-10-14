<?php

namespace RingleSoft\LaravelAccounting;

use Illuminate\Support\Collection;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
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

    /**
     * @return PostingService
     */
    public function posting(): PostingService
    {
        return app(PostingService::class);
    }

    /**
     * @return TemplateRegistry
     */
    public function templates(): TemplateRegistry
    {
        return app(TemplateRegistry::class);
    }

    // - Journal creation and posting
    // - Reversals and adjustments
    // - Period validation and locking
    // - Balance and ledger queries
    // - Report data generation

    /**
     * @return Collection
     */
    public function chartsOfAccounts(): Collection
    {
        return $this->accounts()->getChartOfAccounts();
    }

    /**
     * @param PostableModel $model
     * @return JournalEntry
     */
    public function postModel(PostableModel $model): JournalEntry
    {
        $voucherData = $model->toVoucher();
        $entry = $this->journals()->createDraft($voucherData);
        $this->journals()->post($entry);

        return $entry;
    }

    /**
     * @param VoucherData $voucher
     * @return JournalEntry
     */
    public function postRaw(VoucherData $voucher): JournalEntry
    {
        $entry = $this->journals()->createDraft($voucher);
        $this->journals()->post($entry);

        return $entry;
    }

    /**
     * @throws Throwable
     */
    public function reverse(JournalEntry $journalEntry): JournalEntry
    {
        return $this->journals()->reverse($journalEntry);
    }
}
