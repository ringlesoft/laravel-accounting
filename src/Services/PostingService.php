<?php

namespace RingleSoft\LaravelAccounting\Services;

use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Exceptions\JournalNotBalancedException;
use RingleSoft\LaravelAccounting\Exceptions\PeriodClosedException;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Templates\TemplateRegistry;
use Throwable;

/**
 * Named shortcuts for the standard transaction types, each wrapping the
 * matching registered template plus JournalService::createDraft()+post()
 * in one call. Every method here can throw whatever template resolution
 * and posting can throw: AccountNotFoundException, InvalidVoucherException,
 * JournalNotBalancedException, ParentAccountPostingException (from the
 * template's make()), and PeriodClosedException, DuplicatePostingException
 * (from JournalService). See postWithTemplate() for where these surface.
 */
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
     * Note: This expenses and pays wages in one step, without an accrual.
     * For payroll that was previously accrued via accruedExpense(), use payWages() instead.
     */
    public function payroll(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('payroll.expense');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post payment of previously accrued wages
     * Debit: Wages Payable
     * Credit: Cash
     */
    public function payWages(PostableModel $model, string|int|null $userId = null): JournalEntry
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
        // purchase.on_account/purchase.cash default $expenseAccount to COST_OF_GOODS_SOLD;
        // an inventory purchase needs it overridden to INVENTORY to match this method's own docblock.
        $templateKey = $onAccount ? 'purchase.on_account' : 'purchase.cash';
        $template = TemplateRegistry::make($templateKey, [AccountPreset::INVENTORY]);

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post cost of goods sold
     * Debit: Cost of Goods Sold
     * Credit: Inventory
     */
    public function costOfGoodsSold(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        // inventory.adjustment defaults to the generic INVENTORY_ADJUSTMENTS (shrinkage)
        // preset; COGS recognition needs the dedicated COST_OF_GOODS_SOLD expense account.
        $template = TemplateRegistry::make('inventory.adjustment', [
            AccountPreset::INVENTORY,
            AccountPreset::COST_OF_GOODS_SOLD,
            false,
        ]);

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post bank transfer
     * Debit: Destination Bank Account
     * Credit: Source Bank Account
     * Note: This is a custom transaction that requires manual voucher creation
     *
     * @param int $fromAccountId Unused - $model->toVoucher() must already encode both legs; kept for signature clarity, not read by this method
     * @param int $toAccountId Unused - see $fromAccountId
     * @throws Throwable
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
        $template = TemplateRegistry::make('expense.prepaid.payment');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post amortization of a previously prepaid expense
     * Debit: Expense Account
     * Credit: Prepaid Expense
     */
    public function amortizePrepaidExpense(PostableModel $model, string|int|null $userId = null): JournalEntry
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
        $template = TemplateRegistry::make('revenue.unearned');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post recognition of revenue earned but not yet billed/collected
     * Debit: Accrued Revenue
     * Credit: Sales Revenue
     */
    public function recognizeAccruedRevenue(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('revenue.accrued');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post recognition of previously-received unearned revenue as earned
     * Debit: Unearned Revenue
     * Credit: Sales Revenue
     */
    public function recognizeUnearnedRevenueEarned(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('revenue.unearned_recognition');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post stock issuance
     * Debit: Cash
     * Credit: Common Stock (par value) + Additional Paid-in Capital (excess)
     */
    public function stockIssuance(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('equity.stock_issuance');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post treasury stock reissuance
     * Debit: Cash
     * Credit: Treasury Stock (at cost) + Additional Paid-in Capital (gain over cost)
     */
    public function treasuryStockReissuance(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('equity.treasury_reissuance');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post bond issuance
     * Debit: Cash (net of issuance costs) + Bond Issuance Costs
     * Credit: Bonds Payable (face value)
     */
    public function bondIssuance(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('debt.bond_issuance');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post a sale with an early-payment discount
     * Debit: Cash (net) + Sales Discount
     * Credit: Accounts Receivable (full)
     */
    public function salesDiscount(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('sale.discount');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post a detailed payroll payment including employer payroll tax and withholding
     * Debit: Wages Payable + Payroll Tax Expense
     * Credit: Cash (net of withholding) + Payroll Tax Payable + Federal Withholding Payable
     */
    public function payrollPaymentDetailed(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('payroll.payment_detailed');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post writing off a specific customer's uncollectible balance
     * Debit: Allowance for Doubtful Accounts
     * Credit: Accounts Receivable
     */
    public function writeOffBadDebt(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('debt.writeoff');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post recovery of a previously written-off debt
     * Debit: Cash
     * Credit: Allowance for Doubtful Accounts
     */
    public function recoverBadDebt(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('debt.recovery');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post a fixed asset sale at a gain
     * Debit: Cash + Accumulated Depreciation
     * Credit: Fixed Assets (at cost) + Gain on Sale
     */
    public function assetSaleGain(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('asset.sale_gain');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post a fixed asset sale at a loss
     * Debit: Cash + Accumulated Depreciation + Loss on Sale
     * Credit: Fixed Assets (at cost)
     */
    public function assetSaleLoss(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('asset.sale_loss');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post a fixed asset disposal with no sale (scrapped/retired)
     * Debit: Accumulated Depreciation + Loss on Disposal
     * Credit: Fixed Assets (at cost)
     */
    public function assetDisposal(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('asset.disposal');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post an investment sale at a gain
     * Debit: Cash
     * Credit: Investment (at cost) + Gain on Sale
     */
    public function investmentSaleGain(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('investment.sale_gain');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post an investment sale at a loss
     * Debit: Cash + Loss on Sale
     * Credit: Investment (at cost)
     */
    public function investmentSaleLoss(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('investment.sale_loss');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post a VAT payment net of input VAT
     * Debit: Output VAT
     * Credit: Input VAT (offset) + Cash (net remittance)
     */
    public function vatPayment(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('tax.vat_payment');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post a realized foreign exchange gain
     * Debit: Cash/Accounts Receivable
     * Credit: Foreign Exchange Gain
     */
    public function foreignExchangeGain(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('fx.gain');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post a realized foreign exchange loss
     * Debit: Foreign Exchange Loss
     * Credit: Cash/Accounts Payable
     */
    public function foreignExchangeLoss(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $template = TemplateRegistry::make('fx.loss');

        return $this->postWithTemplate($model, $template, $userId);
    }

    /**
     * Post adjusting entry
     * Custom debit and credit accounts
     * Note: This is a custom transaction that requires the model to implement toVoucher()
     *
     * @throws Throwable
     */
    public function adjustingEntry(PostableModel $model, string|int|null $userId = null): JournalEntry
    {
        $voucher = $model->toVoucher();
        $entry = $this->journalService->createDraft($voucher, $userId);
        $this->journalService->post($entry, $userId);

        return $entry;
    }

    /**
     * Post using a template: resolve its entries against $model, then create
     * and post the resulting journal entry in one step.
     *
     * @param PostableModel $model
     * @param $template
     * @param string|int|null $userId Attributed as created_by/posted_by; null if posting outside an authenticated context (e.g. a queued job)
     *
     * @return JournalEntry
     * @throws Throwable
     */
    protected function postWithTemplate(PostableModel $model, $template, string|int|null $userId = null): JournalEntry
    {
        $voucher = $template->make($model);
        $entry = $this->journalService->createDraft($voucher, $userId);
        $this->journalService->post($entry, $userId);

        return $entry;
    }
}
