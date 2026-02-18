<?php

namespace RingleSoft\LaravelAccounting\Templates;

use RingleSoft\LaravelAccounting\Exceptions\TemplateNotFoundException;

class TemplateRegistry
{
    protected static array $templates = [
        'sale.credit' => CreditSaleTemplate::class,
        'sale.cash' => CashSaleTemplate::class,
        'purchase.on_account' => PurchaseOnAccountTemplate::class,
        'purchase.cash' => CashPurchaseTemplate::class,
        'payment.receipt' => PaymentReceiptTemplate::class,
        'payment.bill' => BillPaymentTemplate::class,
        'payroll.accrual' => PayrollAccrualTemplate::class,
        'payroll.payment' => PayrollPaymentTemplate::class,
        'asset.depreciation' => DepreciationTemplate::class,
        'asset.purchase' => FixedAssetPurchaseTemplate::class,
        'return.sales' => SalesReturnTemplate::class,
        'return.purchase' => PurchaseReturnTemplate::class,
        'revenue.accrued' => AccruedRevenueTemplate::class,
        'expense.prepaid' => PrepaidExpenseTemplate::class,
        'loan.receipt' => LoanReceiptTemplate::class,
        'loan.payment' => LoanPaymentTemplate::class,
        'equity.investment' => OwnerInvestmentTemplate::class,
        'equity.dividend' => DividendTemplate::class,
        'inventory.adjustment' => InventoryAdjustmentTemplate::class,
        'debt.writeoff' => BadDebtTemplate::class,
    ];

    /**
     * @throws TemplateNotFoundException
     */
    public static function make(string $template, array $config = []): AccountingTemplate
    {
        if (! isset(self::$templates[$template])) {
            throw new TemplateNotFoundException("Template {$template} not found");
        }

        return new self::$templates[$template](...$config);
    }

    public static function register(string $key, string $templateClass): void
    {
        self::$templates[$key] = $templateClass;
    }

    /**
     * @return array|string[]
     */
    public static function templates(): array
    {
        return self::$templates;
    }
}
