<?php

namespace RingleSoft\LaravelAccounting\Services;

use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Templates\TemplateRegistry;

class PostingService
{
    protected JournalService $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    /**
     * Post a sale transaction (Invoice)
     * Debit: Accounts Receivable
     * Credit: Sales Revenue
     */
    public function saleTransaction(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('sale.credit');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post a cash sale
     * Debit: Cash
     * Credit: Sales Revenue
     */
    public function cashSale(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('sale.cash');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post receiving payment from customer
     * Debit: Cash
     * Credit: Accounts Receivable
     */
    public function receivingPayment(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('payment.receipt');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post purchase on account
     * Debit: Inventory/Expense
     * Credit: Accounts Payable
     */
    public function purchaseOnAccount(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('purchase.on_account');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post cash purchase
     * Debit: Inventory/Expense
     * Credit: Cash
     */
    public function cashPurchase(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('purchase.cash');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post payment to supplier
     * Debit: Accounts Payable
     * Credit: Cash
     */
    public function paymentToSupplier(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('payment.bill');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post expense payment
     * Debit: Expense Account
     * Credit: Cash
     * Note: For generic expense payments, use cashPurchase instead
     */
    public function expensePayment(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('purchase.cash');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post payroll
     * Debit: Salary Expense
     * Credit: Cash
     */
    public function payroll(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('payroll.payment');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post depreciation
     * Debit: Depreciation Expense
     * Credit: Accumulated Depreciation
     */
    public function depreciation(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('asset.depreciation');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post loan received
     * Debit: Cash
     * Credit: Loan Payable
     */
    public function loanReceived(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('loan.receipt');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post loan payment
     * Debit: Loan Payable
     * Credit: Cash
     */
    public function loanPayment(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('loan.payment');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post owner investment
     * Debit: Cash
     * Credit: Owner's Equity/Share Capital
     */
    public function ownerInvestment(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('equity.investment');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post owner withdrawal/dividend
     * Debit: Dividends/Owner's Drawings
     * Credit: Cash
     */
    public function ownerWithdrawal(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('equity.dividend');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post inventory purchase
     * Debit: Inventory
     * Credit: Cash/Accounts Payable
     */
    public function inventoryPurchase(PostableModel $model, bool $onAccount = false, string|int|null $userId = null): JournalEntry
    {
        $templateKey = $onAccount ? 'purchase.on_account' : 'purchase.cash';
        $template = TemplateRegistry::make($templateKey);
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post cost of goods sold
     * Debit: Cost of Goods Sold
     * Credit: Inventory
     */
    public function costOfGoodsSold(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('inventory.adjustment');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post bank transfer
     * Debit: Destination Bank Account
     * Credit: Source Bank Account
     * Note: This is a custom transaction that requires manual voucher creation
     */
    public function bankTransfer(PostableModel $model, int $fromAccountId, int $toAccountId, string|int|null $userId = null): JournalEntry
    {
        $voucher = $model->toVoucher();
        $entry = $this->journalService->createDraft($voucher, $userId);
        $this->journalService->post($entry, $userId);
        return $entry;
    }

    /**
     * Post accrued expense
     * Debit: Expense Account
     * Credit: Accrued Expenses Payable
     */
    public function accruedExpense(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('payroll.accrual');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post prepaid expense
     * Debit: Prepaid Expense
     * Credit: Cash
     */
    public function prepaidExpense(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('expense.prepaid');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post unearned revenue
     * Debit: Cash
     * Credit: Unearned Revenue
     */
    public function unearnedRevenue(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('revenue.accrued');
        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post adjusting entry
     * Custom debit and credit accounts
     * Note: This is a custom transaction that requires the model to implement toVoucher()
     */
    public function adjustingEntry(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $voucher = $model->toVoucher();
        $entry = $this->journalService->createDraft($voucher, $userId);
        $this->journalService->post($entry, $userId);
        return $entry;
    }

    /**
     * Post using a template
     */
    protected function postWithTemplate(PostableModel $model, $template, string|int|null $userId = null): JournalEntry
    {
        $template->configure();
        $voucher = $template->make($model);
        $entry = $this->journalService->createDraft($voucher, $userId);
        $this->journalService->post($entry, $userId);
        return $entry;
    }
}
