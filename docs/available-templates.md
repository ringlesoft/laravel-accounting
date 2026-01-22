# Available Accounting Templates

## Overview

Accounting templates are pre-configured journal entry patterns that automate the creation of double-entry bookkeeping transactions. Each template encapsulates the accounting logic for a specific business transaction type, ensuring consistency and reducing the likelihood of errors.

Templates use the `AccountPreset` enum to reference standard chart of accounts, making them flexible and adaptable to your specific account structure. When a template is applied to a postable model, it automatically generates the appropriate debit and credit entries based on the transaction amount and configured parameters.

## How Templates Work

1. **Extend `AccountingTemplate`**: All templates extend the base `AccountingTemplate` class
2. **Configure Accounts**: Templates use `AccountPreset` enums to specify which accounts to debit/credit
3. **Define Logic**: The `configure()` method defines the accounting entry pattern
4. **Dynamic Amounts**: Templates support closures for calculating amounts (e.g., tax calculations)
5. **Automatic Application**: Attach templates to models using the `#[DefaultAccountingTemplate]` attribute

## Template Categories

### Sales & Revenue Templates

| Template | Class | Description | Accounts Affected | Parameters |
|----------|-------|-------------|-------------------|------------|
| **Cash Sale** | `CashSaleTemplate` | Records immediate cash sales with optional sales tax | Dr: Cash<br>Cr: Sales Revenue<br>Cr: Sales Tax Payable | `$cashAccount`, `$revenueAccount`, `$taxAccount`, `$taxRate` |
| **Credit Sale** | `CreditSaleTemplate` | Records sales on account (accounts receivable) with optional tax | Dr: Accounts Receivable<br>Cr: Sales Revenue<br>Cr: Sales Tax Payable | `$receivableAccount`, `$revenueAccount`, `$taxAccount`, `$taxRate` |
| **Sales Return** | `SalesReturnTemplate` | Records customer returns for cash or credit sales | Dr: Sales Returns<br>Cr: Accounts Receivable/Cash | `$salesReturnsAccount`, `$receivableAccount`, `$cashAccount`, `$isCashReturn` |
| **Payment Receipt** | `PaymentReceiptTemplate` | Records cash received from customers against receivables | Dr: Cash<br>Cr: Accounts Receivable | `$cashAccount`, `$receivableAccount` |
| **Accrued Revenue** | `AccruedRevenueTemplate` | Records recognition of previously unearned revenue | Dr: Unearned Revenue<br>Cr: Sales Revenue | `$accruedRevenueAccount`, `$revenueAccount` |

### Purchase & Expense Templates

| Template | Class | Description | Accounts Affected | Parameters |
|----------|-------|-------------|-------------------|------------|
| **Cash Purchase** | `CashPurchaseTemplate` | Records immediate cash purchases with optional input tax | Dr: Expense/COGS<br>Dr: Input VAT<br>Cr: Cash | `$expenseAccount`, `$inputTaxAccount`, `$cashAccount`, `$taxRate` |
| **Purchase on Account** | `PurchaseOnAccountTemplate` | Records purchases on credit with optional input tax | Dr: Expense/COGS<br>Dr: Input VAT<br>Cr: Accounts Payable | `$expenseAccount`, `$inputTaxAccount`, `$payableAccount`, `$taxRate` |
| **Purchase Return** | `PurchaseReturnTemplate` | Records returns to suppliers for cash or credit purchases | Dr: Accounts Payable/Cash<br>Cr: Inventory/Expense | `$payableAccount`, `$cashAccount`, `$inventoryAccount`, `$isCashReturn` |
| **Bill Payment** | `BillPaymentTemplate` | Records payment to suppliers against payables | Dr: Accounts Payable<br>Cr: Cash | `$payableAccount`, `$cashAccount` |
| **Prepaid Expense** | `PrepaidExpenseTemplate` | Records amortization of prepaid expenses | Dr: Expense<br>Cr: Prepaid Expense | `$expenseAccount`, `$prepaidExpenseAccount` |

### Payroll Templates

| Template | Class | Description | Accounts Affected | Parameters |
|----------|-------|-------------|-------------------|------------|
| **Payroll Accrual** | `PayrollAccrualTemplate` | Records payroll expense with tax withholding | Dr: Salaries Expense<br>Cr: Wages Payable<br>Cr: Payroll Tax Payable | `$salaryExpenseAccount`, `$wagesPayableAccount`, `$taxPayableAccount`, `$taxWithholdingRate` |
| **Payroll Payment** | `PayrollPaymentTemplate` | Records actual payment of wages to employees | Dr: Wages Payable<br>Cr: Cash | `$wagesPayableAccount`, `$cashAccount` |

### Asset & Depreciation Templates

| Template | Class | Description | Accounts Affected | Parameters |
|----------|-------|-------------|-------------------|------------|
| **Fixed Asset Purchase** | `FixedAssetPurchaseTemplate` | Records acquisition of fixed assets (cash or credit) | Dr: Fixed Assets<br>Cr: Cash/Accounts Payable | `$fixedAssetAccount`, `$cashAccount`, `$payableAccount`, `$isCashPurchase` |
| **Depreciation** | `DepreciationTemplate` | Records periodic depreciation expense | Dr: Depreciation Expense<br>Cr: Accumulated Depreciation | `$depreciationExpenseAccount`, `$accumulatedDepreciationAccount` |

### Inventory Templates

| Template | Class | Description | Accounts Affected | Parameters |
|----------|-------|-------------|-------------------|------------|
| **Inventory Adjustment** | `InventoryAdjustmentTemplate` | Records inventory increases or decreases (shrinkage) | Dr: Inventory/Shrinkage Expense<br>Cr: Shrinkage Expense/Inventory | `$inventoryAccount`, `$shrinkageExpenseAccount`, `$isIncrease` |

### Loan & Financing Templates

| Template | Class | Description | Accounts Affected | Parameters |
|----------|-------|-------------|-------------------|------------|
| **Loan Receipt** | `LoanReceiptTemplate` | Records receipt of loan proceeds | Dr: Cash<br>Cr: Loan Payable | `$cashAccount`, `$loanPayableAccount` |
| **Loan Payment** | `LoanPaymentTemplate` | Records loan principal and interest payments | Dr: Loan Payable<br>Dr: Interest Expense<br>Cr: Cash | `$loanPayableAccount`, `$interestExpenseAccount`, `$cashAccount`, `$interestAmount` |

### Equity Templates

| Template | Class | Description | Accounts Affected | Parameters |
|----------|-------|-------------|-------------------|------------|
| **Owner Investment** | `OwnerInvestmentTemplate` | Records capital contributions by owners | Dr: Cash<br>Cr: Owner's Equity | `$cashAccount`, `$equityAccount` |
| **Dividend** | `DividendTemplate` | Records dividend distributions to shareholders | Dr: Retained Earnings<br>Cr: Cash | `$retainedEarningsAccount`, `$cashAccount` |

### Receivables Management Templates

| Template | Class | Description | Accounts Affected | Parameters |
|----------|-------|-------------|-------------------|------------|
| **Bad Debt** | `BadDebtTemplate` | Records write-off of uncollectible receivables | Dr: Bad Debt Expense<br>Cr: Allowance for Doubtful Accounts | `$badDebtExpenseAccount`, `$allowanceAccount` |

## Usage Examples

### Basic Usage

```php
use RingleSoft\LaravelAccounting\Templates\CashSaleTemplate;

// Create a sale model
$sale = Sale::create([
    'amount' => 1000,
    'customer_id' => 1,
]);

// Apply template and post to journal
$template = new CashSaleTemplate();
$sale->postWithTemplate($template);
```

### Using Default Template Attribute

```php
use RingleSoft\LaravelAccounting\Attributes\DefaultAccountingTemplate;
use RingleSoft\LaravelAccounting\Templates\CreditSaleTemplate;

#[DefaultAccountingTemplate(CreditSaleTemplate::class)]
class Invoice extends Model implements PostableModel
{
    use Postable;
    
    public function getAmount(): float
    {
        return $this->total;
    }
}

// Template is automatically applied
$invoice = Invoice::create(['total' => 5000]);
$invoice->post(); // Uses CreditSaleTemplate automatically
```

### Customizing Template Parameters

```php
use RingleSoft\LaravelAccounting\Templates\CashSaleTemplate;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

// Create template with custom accounts and tax rate
$template = new CashSaleTemplate(
    cashAccount: AccountPreset::PETTY_CASH,
    revenueAccount: AccountPreset::SERVICE_REVENUE,
    taxAccount: AccountPreset::SALES_TAX_PAYABLE,
    taxRate: 0.15 // 15% tax
);

$sale->postWithTemplate($template);
```

### Template with Dynamic Amount Calculation

```php
use RingleSoft\LaravelAccounting\Templates\LoanPaymentTemplate;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

$payment = LoanPayment::create([
    'amount' => 1200,
    'interest' => 200,
]);

$template = new LoanPaymentTemplate(
    loanPayableAccount: AccountPreset::LOAN_PAYABLE,
    interestExpenseAccount: AccountPreset::INTEREST_EXPENSE,
    cashAccount: AccountPreset::CASH,
    interestAmount: $payment->interest
);

$payment->postWithTemplate($template);
// Results in:
// Dr: Loan Payable $1,000
// Dr: Interest Expense $200
// Cr: Cash $1,200
```

### Conditional Template Logic

```php
use RingleSoft\LaravelAccounting\Templates\FixedAssetPurchaseTemplate;

$assetPurchase = AssetPurchase::create([
    'amount' => 50000,
    'payment_method' => 'credit',
]);

$template = new FixedAssetPurchaseTemplate(
    isCashPurchase: $assetPurchase->payment_method === 'cash'
);

$assetPurchase->postWithTemplate($template);
```

## Creating Custom Templates

You can create your own templates by extending `AccountingTemplate`:

```php
namespace App\Accounting\Templates;

use RingleSoft\LaravelAccounting\Templates\AccountingTemplate;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;

class CustomRefundTemplate extends AccountingTemplate
{
    public function __construct(
        protected AccountPreset $salesReturnsAccount = AccountPreset::SALES_RETURNS,
        protected AccountPreset $cashAccount = AccountPreset::CASH,
        protected AccountPreset $taxAccount = AccountPreset::SALES_TAX_PAYABLE,
        protected float $taxRate = 0.0
    ) {}

    public function configure(): void
    {
        // Calculate amounts
        $netAmount = fn($amount) => $amount / (1 + $this->taxRate);
        $taxAmount = fn($amount) => $amount * $this->taxRate / (1 + $this->taxRate);

        // Define entries
        $this->debit($this->salesReturnsAccount, $netAmount);
        
        if ($this->taxRate > 0) {
            $this->debit($this->taxAccount, $taxAmount);
        }
        
        $this->credit($this->cashAccount);
    }
}
```

## Best Practices

1. **Use AccountPreset Enums**: Always use `AccountPreset` enums instead of hardcoding account IDs
2. **Parameterize Templates**: Make templates flexible by accepting account parameters in the constructor
3. **Support Tax Calculations**: Include optional tax parameters for jurisdictions that require them
4. **Document Entry Logic**: Add comments showing the debit/credit pattern (e.g., `// Dr: Cash, Cr: Revenue`)
5. **Use Closures for Dynamic Amounts**: When amounts need calculation, use closures: `fn($amount) => $amount * 0.15`
6. **Test Thoroughly**: Verify that templates produce balanced entries for various scenarios
7. **Consider Edge Cases**: Handle optional entries (e.g., tax only when `$taxRate > 0`)

## Template Registry

All templates are automatically registered and can be discovered programmatically. See the `TemplateRegistry` class for dynamic template management.

## See Also

- [Account Presets Documentation](./account-presets.md)
- [Postable Models Guide](./postable-models.md)
- [Creating Custom Templates](./custom-templates.md)
- [Chart of Accounts Setup](./chart-of-accounts.md)
