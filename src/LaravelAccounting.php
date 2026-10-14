<?php

namespace RingleSoft\LaravelAccounting;

use Illuminate\Support\Collection;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\Exceptions\DuplicatePostingException;
use RingleSoft\LaravelAccounting\Exceptions\ParentAccountPostingException;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Services\AccountService;
use RingleSoft\LaravelAccounting\Services\BalanceService;
use RingleSoft\LaravelAccounting\Services\ClosingService;
use RingleSoft\LaravelAccounting\Services\JournalService;
use RingleSoft\LaravelAccounting\Services\PeriodService;
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

    /**
     * @return PostingService
     */
    public function posting(): PostingService
    {
        return app(PostingService::class);
    }

    /**
     * @return PeriodService
     */
    public function periods(): PeriodService
    {
        return app(PeriodService::class);
    }

    /**
     * @return TemplateRegistry
     */
    public function templates(): TemplateRegistry
    {
        return app(TemplateRegistry::class);
    }

    /**
     * @return ClosingService
     */
    public function closing(): ClosingService
    {
        return app(ClosingService::class);
    }

    /**
     * @return Collection
     */
    public function chartsOfAccounts(): Collection
    {
        return $this->accounts()->getChartOfAccounts();
    }

    /**
     * Create and post a journal entry straight from the model's own toVoucher()/getTemplate() - equivalent to $model->post().
     *
     * @throws DuplicatePostingException
     * @throws ParentAccountPostingException
     * @throws Throwable
     */
    public function postModel(PostableModel $model): JournalEntry
    {
        $voucherData = $model->toVoucher();
        $entry = $this->journals()->createDraft($voucherData);
        $this->journals()->post($entry);

        return $entry;
    }

    /**
     * Create and post a journal entry directly from a hand-built VoucherData, bypassing templates entirely.
     *
     * @throws DuplicatePostingException
     * @throws ParentAccountPostingException
     * @throws Throwable
     */
    public function postRaw(VoucherData $voucher): JournalEntry
    {
        $entry = $this->journals()->createDraft($voucher);
        $this->journals()->post($entry);

        return $entry;
    }

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
