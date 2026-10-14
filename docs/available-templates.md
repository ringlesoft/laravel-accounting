# Available Accounting Templates

## Overview

Accounting templates are pre-configured journal entry patterns that automate the creation of double-entry bookkeeping transactions. Each template encapsulates the accounting logic for a specific business transaction type, ensuring consistency and reducing the likelihood of errors.

Templates use the `AccountPreset` enum to reference standard chart of accounts, making them flexible and adaptable to your specific account structure. When a template is applied to a postable model, it automatically generates the appropriate debit and credit entries based on the transaction amount and configured parameters.

> **Note on `AccountPreset::EXPENSES`**: this preset is a legacy alias and is never assigned to a seeded account. Don't default a template to it — use a real category preset such as `OPERATING_EXPENSES`, or a specific one like `RENT_EXPENSE`, instead. A template whose default preset has no matching account throws `AccountNotFoundException` when `getPostableAccounts()`/`make()` runs.

## How Templates Work

1. **Extend `AccountingTemplate`**: All templates extend the base `AccountingTemplate` class
2. **Configure Accounts**: Templates use `AccountPreset` enums to specify which accounts to debit/credit
3. **Define Logic**: The `configure()` method defines the accounting entry pattern
4. **Dynamic Amounts**: Templates support closures for calculating amounts (e.g., tax calculations)
5. **Automatic Application**: Attach templates to models using the `#[DefaultAccountingTemplate]` attribute

Most templates are also reachable through a convenience method on `PostingService` (`app(PostingService::class)` or `LaravelAccounting::posting()`), which wraps `createDraft()` + `post()` for you. The **PostingService Method** column below names it where one exists; `TemplateRegistry::make('<key>')` always works as the lower-level alternative.

## Accounting Basis: Accrual vs Cash

**Accrual basis** recognizes revenue when it's *earned* and expense when it's *incurred*, independent of when cash actually moves — this is what Accounts Receivable/Payable, Unearned Revenue, Prepaid Expenses, and Accrued Revenue/Expense exist for. **Cash basis** recognizes revenue only when cash is *received* and expense only when cash is *paid* — simpler, but not GAAP/IFRS-compliant above small-business revenue thresholds.

The package ships templates for both styles side by side. Which basis your books end up following is determined entirely by which templates you choose to post with:

| Style | Templates |
|---|---|
| **Accrual** (post through a timing account) | `CreditSaleTemplate`, `PurchaseOnAccountTemplate`, `PayrollAccrualTemplate`, `UnearnedRevenueTemplate`, `AccruedRevenueTemplate`, `PrepaidExpenseTemplate`, `BillPaymentTemplate`, `PayrollPaymentTemplate` |
| **Cash** (post straight to Cash) | `CashSaleTemplate`, `CashPurchaseTemplate`, `PayrollExpenseTemplate`, `PrepaidExpensePaymentTemplate` |

`config('laravel_accounting.cash_basis')` (default `false`) exists to let you *declare* which basis an installation intends to follow. **It is informational only right now** — nothing in the package reads it or enforces it. Setting it to `true` does not stop you from posting through an accrual-only template like `CreditSaleTemplate`; it's on you to consistently pick cash-style templates if that's the basis you want. Nothing currently stops mixing styles across transactions either (e.g. accrual for sales, cash for expenses) — that isn't a recognized "basis," just whatever policy you apply transaction-by-transaction.

## Template Categories

### Sales & Revenue Templates

| Template | Class | Description | Accounts Affected | Parameters | Registry Key | PostingService Method |
|----------|-------|-------------|-------------------|------------|---------------|------------------------|
| **Cash Sale** | `CashSaleTemplate` | Records immediate cash sales with optional sales tax | Dr: `CASH`<br>Cr: `SALES_REVENUE`<br>Cr: `SALES_TAX_PAYABLE` (if `taxRate` > 0) | `$cashAccount`, `$revenueAccount`, `$taxAccount`, `$taxRate` | `sale.cash` | `cashSale()` |
| **Credit Sale** | `CreditSaleTemplate` | Records sales on account (accounts receivable) with optional tax | Dr: `ACCOUNTS_RECEIVABLE`<br>Cr: `SALES_REVENUE`<br>Cr: `SALES_TAX_PAYABLE` (if `taxRate` > 0) | `$receivableAccount`, `$revenueAccount`, `$taxAccount`, `$taxRate` | `sale.credit` | `saleTransaction()` |
| **Sales Return** | `SalesReturnTemplate` | Records customer returns for cash or credit sales | Dr: `SALES_RETURNS`<br>Cr: `ACCOUNTS_RECEIVABLE` / `CASH` | `$salesReturnsAccount`, `$receivableAccount`, `$cashAccount`, `$isCashReturn` | `return.sales` | *(none — use `TemplateRegistry::make()`)* |
| **Payment Receipt** | `PaymentReceiptTemplate` | Records cash received from customers against receivables | Dr: `CASH`<br>Cr: `ACCOUNTS_RECEIVABLE` | `$cashAccount`, `$receivableAccount` | `payment.receipt` | `receivingPayment()` |
| **Accrued Revenue** | `AccruedRevenueTemplate` | Records revenue that has been earned but not yet billed or collected | Dr: `ACCRUED_REVENUE`<br>Cr: `SALES_REVENUE` | `$accruedRevenueAccount`, `$revenueAccount` | `revenue.accrued` | `recognizeAccruedRevenue()` |
| **Unearned Revenue** | `UnearnedRevenueTemplate` | Records cash received in advance of delivering the goods/service | Dr: `CASH`<br>Cr: `UNEARNED_REVENUE` | `$cashAccount`, `$unearnedRevenueAccount` | `revenue.unearned` | `unearnedRevenue()` |

> `AccruedRevenueTemplate` and `UnearnedRevenueTemplate` are opposite-timing events, not variants of each other: Accrued Revenue is earned-but-not-billed (an asset); Unearned Revenue is billed/collected-but-not-earned (a liability). There is currently no template for the third step — recognizing previously-unearned revenue as earned (`Dr UNEARNED_REVENUE / Cr SALES_REVENUE`) once the good/service is delivered. See **Known Gaps** below.

### Purchase & Expense Templates

| Template | Class | Description | Accounts Affected | Parameters | Registry Key | PostingService Method |
|----------|-------|-------------|-------------------|------------|---------------|------------------------|
| **Cash Purchase** | `CashPurchaseTemplate` | Records immediate cash purchases with optional input tax | Dr: `COST_OF_GOODS_SOLD`<br>Dr: `INPUT_VAT` (if `taxRate` > 0)<br>Cr: `CASH` | `$expenseAccount`, `$inputTaxAccount`, `$cashAccount`, `$taxRate` | `purchase.cash` | `cashPurchase()`, `expensePayment()` |
| **Purchase on Account** | `PurchaseOnAccountTemplate` | Records purchases on credit with optional input tax | Dr: `COST_OF_GOODS_SOLD`<br>Dr: `INPUT_VAT` (if `taxRate` > 0)<br>Cr: `ACCOUNTS_PAYABLE` | `$expenseAccount`, `$inputTaxAccount`, `$payableAccount`, `$taxRate` | `purchase.on_account` | `purchaseOnAccount()` |
| **Purchase Return** | `PurchaseReturnTemplate` | Records returns to suppliers for cash or credit purchases | Dr: `ACCOUNTS_PAYABLE` / `CASH`<br>Cr: `INVENTORY` | `$payableAccount`, `$cashAccount`, `$inventoryAccount`, `$isCashReturn` | `return.purchase` | *(none — use `TemplateRegistry::make()`)* |
| **Bill Payment** | `BillPaymentTemplate` | Records payment to suppliers against payables | Dr: `ACCOUNTS_PAYABLE`<br>Cr: `CASH` | `$payableAccount`, `$cashAccount` | `payment.bill` | `paymentToSupplier()` |
| **Prepaid Expense Payment** | `PrepaidExpensePaymentTemplate` | Records the initial cash payment for a future expense | Dr: `PREPAID_EXPENSES`<br>Cr: `CASH` | `$prepaidExpenseAccount`, `$cashAccount` | `expense.prepaid.payment` | `prepaidExpense()` |
| **Prepaid Expense Amortization** | `PrepaidExpenseTemplate` | Records recognition of a previously prepaid expense as it's consumed | Dr: `OPERATING_EXPENSES`<br>Cr: `PREPAID_EXPENSES` | `$expenseAccount`, `$prepaidExpenseAccount` | `expense.prepaid` | `amortizePrepaidExpense()` |

`$expenseAccount` on `CashPurchaseTemplate`/`PurchaseOnAccountTemplate` is any `AccountPreset` you choose — `COST_OF_GOODS_SOLD` is just the default for a goods/inventory purchase; pass a specific expense preset (e.g. `RENT_EXPENSE`) for a service/general expense instead.

### Payroll Templates

| Template | Class | Description | Accounts Affected | Parameters | Registry Key | PostingService Method |
|----------|-------|-------------|-------------------|------------|---------------|------------------------|
| **Payroll Expense** | `PayrollExpenseTemplate` | Records payroll expensed and paid in one step (no accrual) | Dr: `SALARIES_EXPENSE`<br>Cr: `CASH` | `$salaryExpenseAccount`, `$cashAccount` | `payroll.expense` | `payroll()` |
| **Payroll Accrual** | `PayrollAccrualTemplate` | Records payroll expense with tax withholding, before it's paid | Dr: `SALARIES_EXPENSE`<br>Cr: `WAGES_PAYABLE`<br>Cr: `PAYROLL_TAX_PAYABLE` (if `taxWithholdingRate` > 0) | `$salaryExpenseAccount`, `$wagesPayableAccount`, `$taxPayableAccount`, `$taxWithholdingRate` | `payroll.accrual` | `accruedExpense()` |
| **Payroll Payment** | `PayrollPaymentTemplate` | Records payment of previously-accrued wages | Dr: `WAGES_PAYABLE`<br>Cr: `CASH` | `$wagesPayableAccount`, `$cashAccount` | `payroll.payment` | `payWages()` |

`payroll()` (Payroll Expense) and `accruedExpense()` + `payWages()` (Payroll Accrual then Payment) are two different workflows for the same real-world event — pick one depending on whether your business accrues payroll before paying it or expenses it directly at payment time.

### Asset & Depreciation Templates

| Template | Class | Description | Accounts Affected | Parameters | Registry Key | PostingService Method |
|----------|-------|-------------|-------------------|------------|---------------|------------------------|
| **Fixed Asset Purchase** | `FixedAssetPurchaseTemplate` | Records acquisition of fixed assets (cash or credit) | Dr: `FIXED_ASSETS`<br>Cr: `CASH` / `ACCOUNTS_PAYABLE` | `$fixedAssetAccount`, `$cashAccount`, `$payableAccount`, `$isCashPurchase` | `asset.purchase` | *(none — use `TemplateRegistry::make()`)* |
| **Depreciation** | `DepreciationTemplate` | Records periodic depreciation expense | Dr: `DEPRECIATION_EXPENSE`<br>Cr: `ACCUMULATED_DEPRECIATION` | `$depreciationExpenseAccount`, `$accumulatedDepreciationAccount` | `asset.depreciation` | `depreciation()` |

### Inventory Templates

| Template | Class | Description | Accounts Affected | Parameters | Registry Key | PostingService Method |
|----------|-------|-------------|-------------------|------------|---------------|------------------------|
| **Inventory Adjustment** | `InventoryAdjustmentTemplate` | Records inventory increases, or decreases from shrinkage | `isIncrease=true`: Dr `INVENTORY` / Cr `INVENTORY_ADJUSTMENTS`<br>`isIncrease=false`: Dr `INVENTORY_ADJUSTMENTS` / Cr `INVENTORY` | `$inventoryAccount`, `$shrinkageExpenseAccount`, `$isIncrease` | `inventory.adjustment` | *(none directly — see below)* |

`PostingService::costOfGoodsSold()` reuses this same template, but overrides `$shrinkageExpenseAccount` to `COST_OF_GOODS_SOLD` so a sale's cost of goods sold posts `Dr COST_OF_GOODS_SOLD / Cr INVENTORY` instead of the shrinkage default. `PostingService::inventoryPurchase()` similarly reuses `PurchaseOnAccountTemplate`/`CashPurchaseTemplate` with `$expenseAccount` overridden to `INVENTORY`.

### Loan & Financing Templates

| Template | Class | Description | Accounts Affected | Parameters | Registry Key | PostingService Method |
|----------|-------|-------------|-------------------|------------|---------------|------------------------|
| **Loan Receipt** | `LoanReceiptTemplate` | Records receipt of loan proceeds | Dr: `CASH`<br>Cr: `LOAN_PAYABLE` | `$cashAccount`, `$loanPayableAccount` | `loan.receipt` | `loanReceived()` |
| **Loan Payment** | `LoanPaymentTemplate` | Records loan principal and interest payments | Dr: `LOAN_PAYABLE`<br>Dr: `INTEREST_EXPENSE` (if `interestAmount` > 0)<br>Cr: `CASH` | `$loanPayableAccount`, `$interestExpenseAccount`, `$cashAccount`, `$interestAmount` | `loan.payment` | `loanPayment()` |

### Equity Templates

| Template | Class | Description | Accounts Affected | Parameters | Registry Key | PostingService Method |
|----------|-------|-------------|-------------------|------------|---------------|------------------------|
| **Owner Investment** | `OwnerInvestmentTemplate` | Records capital contributions by owners | Dr: `CASH`<br>Cr: `OWNERS_EQUITY` | `$cashAccount`, `$equityAccount` | `equity.investment` | `ownerInvestment()` |
| **Dividend** | `DividendTemplate` | Records a dividend/owner's-draw declared and paid in one step | Dr: `DIVIDENDS_DECLARED`<br>Cr: `CASH` | `$dividendsAccount`, `$cashAccount` | `equity.dividend` | `ownerWithdrawal()` |

`DividendTemplate` collapses declaration and payment into a single entry. If you need the two-step textbook flow (`Dr RETAINED_EARNINGS / Cr DIVIDENDS_PAYABLE` at declaration, then `Dr DIVIDENDS_PAYABLE / Cr CASH` at payment), that isn't implemented — see **Known Gaps**.

### Receivables Management Templates

| Template | Class | Description | Accounts Affected | Parameters | Registry Key | PostingService Method |
|----------|-------|-------------|-------------------|------------|---------------|------------------------|
| **Bad Debt** | `BadDebtTemplate` | Records the *estimate* of uncollectible receivables (not a specific write-off) | Dr: `BAD_DEBT_EXPENSE`<br>Cr: `ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS` | `$badDebtExpenseAccount`, `$allowanceAccount` | `debt.writeoff` | *(none — use `TemplateRegistry::make()`)* |

Despite its registry key (`debt.writeoff`) and class name, this template records the *allowance estimation* step, not writing off one specific customer's balance (`Dr ALLOWANCE... / Cr ACCOUNTS_RECEIVABLE`) — that specific write-off, and recovering a previously written-off debt, aren't implemented. See **Known Gaps**.

## Known Gaps

Correct, standard accounting events that are **not yet implemented** as a template (confirmed by checking every registered template and `PostingService` method):

- **Recognizing previously-unearned revenue as earned** (`Dr UNEARNED_REVENUE / Cr SALES_REVENUE`) — the counterpart to `UnearnedRevenueTemplate`.
- **Bad debt write-off of a specific account** (`Dr ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS / Cr ACCOUNTS_RECEIVABLE`) and **bad debt recovery** (`Dr CASH / Cr ALLOWANCE...`) — only the estimation step exists.
- **Two-step dividend** (declare to a payable, then pay it) — only the one-step version exists.
- **Fixed asset sale/disposal** (removing the asset and its accumulated depreciation, recognizing a gain or loss) — no template at all; only *purchase* of fixed assets is covered.
- **Closing entries** (closing Revenue/Expense to Income Summary, Income Summary to Retained Earnings, Dividends to Retained Earnings) — the package has no closing-entry mechanism; see `roadmap.md`'s "Future Consideration" list.

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
// Dr: `LOAN_PAYABLE` $1,000
// Dr: `INTEREST_EXPENSE` $200
// Cr: `CASH` $1,200
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

### Using it

A template on its own doesn't post anything — you still apply it to a model, the same way as a shipped template:

```php
// Directly:
$sale->postWithTemplate(new CustomRefundTemplate(taxRate: 0.15));

// Or as the model's default, via attribute:
#[DefaultAccountingTemplate(CustomRefundTemplate::class)]
class Sale extends Model implements PostableModel { use Postable; /* ... */ }
```

If you also want it resolvable by key (`TemplateRegistry::make('refund.custom')`) or selectable in the Journal Voucher "Posting Template" picker, register it — nothing does this for you automatically, only the 23 shipped templates are pre-registered:

```php
use RingleSoft\LaravelAccounting\Templates\TemplateRegistry;

TemplateRegistry::register('refund.custom', CustomRefundTemplate::class);
```

Register it from a service provider's `boot()` so it's available everywhere, not just where you happen to reference the class.

### Rejecting a bad model or parameter

Override `validate(PostableModel $model): void` to reject an invalid combination before any account lookup happens — it's called automatically by `make()` right after `configure()`. It's a no-op by default; throw `InvalidVoucherException` (or any exception) to reject:

```php
public function validate(PostableModel $model): void
{
    if ($this->taxRate < 0 || $this->taxRate > 1) {
        throw new \RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException(
            "taxRate must be between 0 and 1, got {$this->taxRate}"
        );
    }
}
```

This is the cleaner alternative to overriding `make()` just to add a guard clause — you don't need to remember to call `parent::make($model)`.

### Testing it before wiring it to a real model

Call `configure()` and inspect the entries directly — no database, no model, no posting required:

```php
$template = new CustomRefundTemplate(taxRate: 0.15);
$template->configure();

$debits = $credits = 0;
foreach ($template->getEntries() as $entry) {
    $amount = $entry->getAmount(1000.0); // simulate a $1000 transaction
    $entry->type === \RingleSoft\LaravelAccounting\Enums\EntryType::DEBIT
        ? $debits += $amount
        : $credits += $amount;
}

abs($debits - $credits) < 0.01 || throw new \RuntimeException('Not balanced!');
```

Then confirm every preset you referenced actually resolves to a seeded account: `YourTemplate::getPostableAccounts()` should return non-empty `DEBIT` and `CREDIT` arrays (see **Gotchas** below for what happens when it can't).

### Gotchas

- **Every constructor parameter needs a default value.** `getPostableAccounts()` and `TemplateRegistry::make('key')` (called with no `$config`) both do `new static()`/`new $class()` with **zero arguments**. A required parameter throws `ArgumentCountError` — not an `AccountNotFoundException` or any package exception, a raw PHP error. This only matters for those two call paths; if you only ever construct the template yourself (`new MyTemplate($required)`) before calling `postWithTemplate()`, a required parameter is fine.
- **Never call `->configure()` yourself.** `make()` (invoked by `postWithTemplate()`/`createJournal()`) already calls it internally. Calling it again first will duplicate every line, since entries are only ever appended, never replaced.
- **Calling `debit()`/`credit()` twice for the same account appends two lines, it doesn't merge them.** That's usually what you want (e.g. two differently-memo'd lines), but if you want one combined line instead, call `$this->deDuplicate()` at the end of `configure()`.
- **A preset with no matching seeded account throws `AccountNotFoundException` at posting time, not at template-definition time.** Always verify with `YourTemplate::getPostableAccounts()` before shipping a default.
- **A preset pointing at a parent/rollup account (one with children) posts successfully through `make()`/`createDraft()`, then fails at `post()`** with `ParentAccountPostingException` (unless `allow_posting_on_parents` is `true`). Prefer a specific leaf preset, or resolve one dynamically — see `getLastChildrenByPreset()` on `AccountService`.
- **There's no built-in way to extend `PostingService`** with a convenience method for your own template — it's a fixed class with one method per shipped template. Call `postWithTemplate()` directly, or register your template with `TemplateRegistry` and call `TemplateRegistry::make('your.key')`.

## Best Practices

1. **Use AccountPreset Enums**: Always use `AccountPreset` enums instead of hardcoding account IDs
2. **Parameterize Templates**: Make templates flexible by accepting account parameters in the constructor — and give every one a default value (see **Gotchas**)
3. **Support Tax Calculations**: Include optional tax parameters for jurisdictions that require them
4. **Document Entry Logic**: Add comments showing the debit/credit pattern (e.g., `// Dr: `CASH`, Cr: Revenue`)
5. **Use Closures for Dynamic Amounts**: When amounts need calculation, use closures: `fn($amount) => $amount * 0.15`
6. **Test Thoroughly**: Use the "Testing it" snippet above to verify balance and account resolution before wiring a template to a real model
7. **Consider Edge Cases**: Handle optional entries (e.g., tax only when `$taxRate > 0`)
8. **Pick a real default preset**: Never default a template to a category/legacy preset unless an account actually carries it — check with `php artisan tinker` and `Account::where('preset', '...')->exists()`, or call `YourTemplate::getPostableAccounts()` and confirm both `DEBIT` and `CREDIT` come back non-empty.

## Template Registry

The 23 shipped templates are pre-registered in `TemplateRegistry::$templates`; your own custom templates are **not** added automatically — call `TemplateRegistry::register('your.key', YourTemplate::class)` yourself if you want key-based resolution or a slot in the Journal Voucher UI's template picker. Any registered template can be resolved via `TemplateRegistry::make('<key>')`. The **Registry Key** column in each table above is the exact key to use for the shipped ones.

## See Also

- [README.md](../README.md) — installation, configuration, and general usage
- [roadmap.md](../roadmap.md) — implementation status and planned features, including the closing-entries gap noted above
