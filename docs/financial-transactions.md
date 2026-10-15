# Financial Transactions

This is a reference of standard accounting transactions and the debit/credit pattern each one follows. Every account preset named below exists in `AccountPreset` and is accounting-correct per standard double-entry practice — this list is broader than what the package ships templates for.

**Status legend:**
- ✅ **Implemented** — a template or `PostingService` method posts this exact transaction by default, no parameter overrides needed.
- 🔧 **Postable today** — no dedicated template, but the transaction is a plain debit/credit pair (optionally with a tax-style split) that an existing template can post once you pass it the right `AccountPreset`s, or that you can post directly via the generic Journal Voucher UI / a manual `VoucherData`.

> `AccountPreset::EXPENSES` is a legacy alias never assigned to a seeded account — rows below use `OPERATING_EXPENSES` (a real, seeded parent expense category) wherever a generic "any expense account" placeholder is needed. Substitute your own specific expense preset (e.g. `RENT_EXPENSE`) in real use.

| # | Transaction | Description | Affected Accounts (DR/CR) | Status |
|---|---|---|---|---|
| | **I. EQUITY TRANSACTIONS** | | | |
| 1 | Capital Contribution Cash | Owner invests cash into business | DR: `CASH`<br>CR: `OWNERS_EQUITY` / `COMMON_STOCK` | ✅ `OwnerInvestmentTemplate` / `ownerInvestment()` |
| 2 | Capital Contribution Non-Cash | Owner invests assets (equipment, etc.) | DR: `MACHINERY_AND_EQUIPMENT`<br>CR: `OWNERS_EQUITY` / `ADDITIONAL_PAID_IN_CAPITAL` | 🔧 `OwnerInvestmentTemplate` with `$cashAccount` overridden to the asset received |
| 3 | Stock Issuance | Company issues shares for cash | DR: `CASH`<br>CR: `COMMON_STOCK`<br>CR: `ADDITIONAL_PAID_IN_CAPITAL` | ✅ `StockIssuanceTemplate` / `stockIssuance()` |
| 4 | Owner Withdrawal | Owner takes cash from business | DR: `OWNERS_DRAW` / `DRAWINGS`<br>CR: `CASH` | 🔧 `DividendTemplate` with `$dividendsAccount` overridden to `DRAWINGS` |
| 5 | Dividend Declaration | Board declares dividend | DR: `RETAINED_EARNINGS`<br>CR: `DIVIDENDS_PAYABLE` | 🔧 plain 2-line entry, no dedicated template |
| 6 | Dividend Payment | Pay declared dividends | DR: `DIVIDENDS_PAYABLE`<br>CR: `CASH` | 🔧 plain 2-line entry — see note below |
| 7 | Treasury Stock Purchase | Company buys back own shares | DR: `TREASURY_STOCK`<br>CR: `CASH` | 🔧 `BillPaymentTemplate` shape fits (Dr/Cr only, override presets) |
| 8 | Treasury Stock Reissuance | Resell treasury shares | DR: `CASH`<br>CR: `TREASURY_STOCK`<br>CR: `ADDITIONAL_PAID_IN_CAPITAL` | ✅ `TreasuryStockReissuanceTemplate` / `treasuryStockReissuance()` - reissuing at or above cost only; below cost is a loss this template doesn't model |
| 9 | Stock Option Expense | Record employee stock compensation | DR: `SALARIES_EXPENSE`<br>CR: `ADDITIONAL_PAID_IN_CAPITAL` | 🔧 plain 2-line entry |
| 10 | Donated Capital | Receive donated assets | DR: `MACHINERY_AND_EQUIPMENT`<br>CR: `DONATED_CAPITAL` | 🔧 plain 2-line entry |
| | **II. ASSET TRANSACTIONS** | | | |
| 11 | Asset Purchase Cash | Buy fixed asset with cash | DR: `FIXED_ASSETS`<br>CR: `CASH` | ✅ `FixedAssetPurchaseTemplate` (`isCashPurchase: true`) |
| 12 | Asset Purchase Credit | Buy fixed asset on credit | DR: `FIXED_ASSETS`<br>CR: `ACCOUNTS_PAYABLE` / `NOTES_PAYABLE` | ✅ `FixedAssetPurchaseTemplate` (`isCashPurchase: false`) |
| 13 | Asset Purchase Loan | Buy asset financed by loan | DR: `FIXED_ASSETS`<br>CR: `LONG_TERM_DEBT` | 🔧 `FixedAssetPurchaseTemplate` with `$payableAccount` overridden to `LONG_TERM_DEBT` |
| 14 | Asset Sale | Sell fixed asset | DR: `CASH`<br>DR: `ACCUMULATED_DEPRECIATION`<br>CR: `FIXED_ASSETS`<br>CR: `GAIN_ON_SALE_OF_ASSETS` (or DR `LOSS_ON_SALE_OF_ASSETS`) | ✅ `AssetSaleGainTemplate` / `assetSaleGain()` (gain) and `AssetSaleLossTemplate` / `assetSaleLoss()` (loss) - `$originalCost`/`$accumulatedDepreciation` are caller-supplied, not queried |
| 15 | Asset Disposal | Dispose asset (no sale) | DR: `ACCUMULATED_DEPRECIATION`<br>DR: `LOSS_ON_SALE_OF_ASSETS`<br>CR: `FIXED_ASSETS` | ✅ `AssetDisposalTemplate` / `assetDisposal()` |
| 16 | Depreciation | Record periodic depreciation | DR: `DEPRECIATION_EXPENSE`<br>CR: `ACCUMULATED_DEPRECIATION` | ✅ `DepreciationTemplate` / `depreciation()` |
| 17 | Amortization | Amortize intangible assets | DR: `AMORTIZATION_EXPENSE`<br>CR: `ACCUMULATED_AMORTIZATION` | 🔧 `DepreciationTemplate` with both presets overridden |
| 18 | Asset Impairment | Write down asset value | DR: `IMPAIRMENT_LOSS`<br>CR: `ACCUMULATED_IMPAIRMENT` | 🔧 plain 2-line entry |
| 19 | Goodwill Impairment | Write down goodwill | DR: `IMPAIRMENT_LOSS`<br>CR: `GOODWILL` | 🔧 plain 2-line entry |
| 20 | Intangible Asset Purchase | Acquire patent, trademark, etc. | DR: `INTANGIBLE_ASSETS`<br>CR: `CASH` | 🔧 `FixedAssetPurchaseTemplate` with `$fixedAssetAccount` overridden |
| 21 | Right of Use Asset Depreciation | Depreciate leased asset (IFRS 16) | DR: `DEPRECIATION_EXPENSE`<br>CR: `ACCUMULATED_DEPRECIATION` | 🔧 `DepreciationTemplate` with presets overridden to `RIGHT_OF_USE_ASSETS` |
| | **III. REVENUE TRANSACTIONS** | | | |
| 22 | Cash Sale | Immediate payment for goods/services | DR: `CASH`<br>CR: `SALES_REVENUE`<br>CR: `SALES_TAX_PAYABLE` | ✅ `CashSaleTemplate` / `cashSale()` |
| 23 | Credit Sale | Sale on account | DR: `ACCOUNTS_RECEIVABLE`<br>CR: `SALES_REVENUE`<br>CR: `SALES_TAX_PAYABLE` | ✅ `CreditSaleTemplate` / `saleTransaction()` |
| 24 | Advance Payment Receipt | Customer pays in advance | DR: `CASH`<br>CR: `UNEARNED_REVENUE` / `CUSTOMER_DEPOSITS` | ✅ `UnearnedRevenueTemplate` / `unearnedRevenue()` |
| 25 | Unearned Revenue Recognition | Recognize previously received advance | DR: `UNEARNED_REVENUE`<br>CR: `SALES_REVENUE` | ✅ `UnearnedRevenueRecognitionTemplate` / `recognizeUnearnedRevenueEarned()` - the counterpart to row 24 |
| 26 | Sales Return | Customer returns goods | DR: `SALES_RETURNS`<br>DR: `SALES_TAX_PAYABLE`<br>CR: `ACCOUNTS_RECEIVABLE` / `CASH` | 🔧 `SalesReturnTemplate` posts the return but has no tax-reversal line |
| 27 | Sales Discount | Early payment discount given | DR: `CASH`<br>DR: `SALES_DISCOUNTS`<br>CR: `ACCOUNTS_RECEIVABLE` | ✅ `SalesDiscountTemplate` / `salesDiscount()` |
| 28 | Credit Memo Issue | Issue credit to customer | DR: `SALES_RETURNS` / `SALES_ALLOWANCES`<br>CR: `ACCOUNTS_RECEIVABLE` | 🔧 `SalesReturnTemplate` (non-cash branch) |
| 29 | Payment Receipt | Receive payment from customer | DR: `CASH_IN_BANK`<br>CR: `ACCOUNTS_RECEIVABLE` | ✅ `PaymentReceiptTemplate` / `receivingPayment()` (defaults to `CASH`, not `CASH_IN_BANK` — override if needed) |
| 30 | Customer Refund | Refund customer overpayment | DR: `ACCOUNTS_RECEIVABLE` / `SALES_RETURNS`<br>CR: `CASH` | 🔧 plain 2-line entry |
| 31 | Accrued Revenue | Revenue earned but not billed | DR: `ACCOUNTS_RECEIVABLE` / `UNBILLED_REVENUE`<br>CR: `SERVICE_REVENUE` | ✅ `AccruedRevenueTemplate` / `recognizeAccruedRevenue()` (defaults to the dedicated `ACCRUED_REVENUE` asset preset rather than `ACCOUNTS_RECEIVABLE`/`UNBILLED_REVENUE`) |
| | **IV. EXPENSE TRANSACTIONS** | | | |
| 32 | Expense Payment Cash | Pay expense immediately | DR: `OPERATING_EXPENSES`<br>CR: `CASH` | 🔧 `CashPurchaseTemplate` with `$expenseAccount` overridden |
| 33 | Expense Incurred Credit | Expense on account | DR: `OPERATING_EXPENSES`<br>CR: `ACCOUNTS_PAYABLE` | 🔧 `PurchaseOnAccountTemplate` with `$expenseAccount` overridden |
| 34 | Prepaid Expense Payment | Pay expense in advance | DR: `PREPAID_EXPENSES`<br>CR: `CASH` | ✅ `PrepaidExpensePaymentTemplate` / `prepaidExpense()` |
| 35 | Prepaid Expense Recognition | Recognize portion of prepaid | DR: `OPERATING_EXPENSES`<br>CR: `PREPAID_EXPENSES` | ✅ `PrepaidExpenseTemplate` / `amortizePrepaidExpense()` |
| 36 | Payroll Accrual | Record earned but unpaid wages | DR: `SALARIES_EXPENSE` / `WAGES_EXPENSE`<br>CR: `SALARIES_PAYABLE` / `WAGES_PAYABLE` | ✅ `PayrollAccrualTemplate` / `accruedExpense()` |
| 37 | Payroll Payment | Pay employee wages | DR: `SALARIES_PAYABLE` / `WAGES_PAYABLE`<br>DR: `PAYROLL_TAX_EXPENSE`<br>CR: `CASH`<br>CR: `PAYROLL_TAX_PAYABLE`<br>CR: `FEDERAL_WITHHOLDING_PAYABLE` | ✅ `PayrollPaymentDetailedTemplate` / `payrollPaymentDetailed()` - the simpler `PayrollPaymentTemplate` (`Dr WAGES_PAYABLE / Cr CASH` only) is unchanged and still available for payrolls with no employer tax/withholding split |
| 38 | Accrued Expense | Record expense incurred, not paid | DR: `OPERATING_EXPENSES`<br>CR: `ACCRUED_EXPENSES` | 🔧 `PayrollAccrualTemplate` shape fits (override both presets), or a plain 2-line entry |
| 39 | Supplies Used | Recognize supplies consumed | DR: `OFFICE_SUPPLIES_EXPENSE`<br>CR: `SUPPLIES` | 🔧 `PrepaidExpenseTemplate` shape fits (override both presets) |
| 40 | Prepaid Insurance Expired | Recognize insurance expense | DR: `INSURANCE_EXPENSE`<br>CR: `PREPAID_INSURANCE` | 🔧 `PrepaidExpenseTemplate` with presets overridden |
| 41 | Payroll Tax Payment | Pay employer taxes to government | DR: `PAYROLL_TAX_PAYABLE`<br>CR: `CASH` | 🔧 `BillPaymentTemplate` with `$payableAccount` overridden |
| 42 | Employee Advance | Loan to employee | DR: `ADVANCE_TO_EMPLOYEES`<br>CR: `CASH` | 🔧 plain 2-line entry |
| 43 | Employee Advance Recovery | Deduct from payroll | DR: `SALARIES_PAYABLE`<br>CR: `ADVANCE_TO_EMPLOYEES` | 🔧 plain 2-line entry (no cash leg) |
| 44 | Employee Reimbursement | Reimburse employee expenses | DR: `OPERATING_EXPENSES`<br>CR: `CASH` | 🔧 `CashPurchaseTemplate`/`PayrollExpenseTemplate` with presets overridden |
| 45 | Bonus Payment | Pay employee bonuses | DR: `BONUS_EXPENSE` / `BONUSES_PAYABLE`<br>CR: `CASH` | 🔧 `PayrollExpenseTemplate` with presets overridden |
| 46 | Commission Payment | Pay sales commissions | DR: `COMMISSION_EXPENSE` / `COMMISSIONS_PAYABLE`<br>CR: `CASH` | 🔧 `PayrollExpenseTemplate` with presets overridden |
| | **V. LIABILITY TRANSACTIONS** | | | |
| 47 | Loan Receipt | Receive loan proceeds | DR: `CASH`<br>CR: `LOAN_PAYABLE` | ✅ `LoanReceiptTemplate` / `loanReceived()` |
| 48 | Interest Accrual | Accrue interest on loan | DR: `INTEREST_EXPENSE`<br>CR: `ACCRUED_INTEREST` | 🔧 plain 2-line entry |
| 49 | Bill Payment | Pay supplier invoice | DR: `ACCOUNTS_PAYABLE`<br>CR: `CASH` | ✅ `BillPaymentTemplate` / `paymentToSupplier()` |
| 50 | Loan Payment | Repay principal + interest | DR: `LOAN_PAYABLE`<br>DR: `INTEREST_EXPENSE` / `ACCRUED_INTEREST`<br>CR: `CASH` | ✅ `LoanPaymentTemplate` / `loanPayment()` |
| 51 | Accrued Expense Payment | Pay previously accrued expense | DR: `ACCRUED_EXPENSES`<br>CR: `CASH` | 🔧 `BillPaymentTemplate` with `$payableAccount` overridden |
| 52 | Bond Issuance | Issue corporate bonds | DR: `CASH`<br>CR: `BONDS_PAYABLE`<br>DR: `BOND_ISSUANCE_COSTS` | ✅ `BondIssuanceTemplate` / `bondIssuance()` - `$model->getAmount()` is the face value; `issuanceCosts` is a flat amount, not a rate |
| 53 | Payment on Account | Pay supplier without specific invoice | DR: `ACCOUNTS_PAYABLE`<br>CR: `CASH` | ✅ `BillPaymentTemplate` (same as row 49) |
| 54 | Supplier Refund | Receive refund from supplier | DR: `CASH`<br>CR: `ACCOUNTS_PAYABLE` / `OPERATING_EXPENSES` | 🔧 plain 2-line entry |
| 55 | Lease Liability Payment | Pay lease (IFRS 16/ASC 842) | DR: `LEASE_LIABILITIES`<br>DR: `INTEREST_EXPENSE`<br>CR: `CASH` | 🔧 `LoanPaymentTemplate` shape fits exactly (override `$loanPayableAccount` to `LEASE_LIABILITIES`) |
| 56 | Operating Lease Payment | Pay operating lease | DR: `RENT_EXPENSE` / `LEASE_EXPENSE`<br>CR: `CASH` | 🔧 `CashPurchaseTemplate`/`BillPaymentTemplate` with presets overridden |
| 57 | Capital Lease | Record capital lease | DR: `LEASED_ASSET`<br>CR: `LEASE_LIABILITIES` | 🔧 `FixedAssetPurchaseTemplate` with presets overridden |
| 58 | Intercompany Loan | Loan between related entities | DR: `OTHER_RECEIVABLES`<br>CR: `CASH` | 🔧 plain 2-line entry |
| | **VI. INVENTORY & COGS** | | | |
| 59 | Inventory Purchase | Buy inventory for resale | DR: `INVENTORY`<br>DR: `INPUT_VAT`<br>CR: `CASH` / `ACCOUNTS_PAYABLE` | ✅ `PostingService::inventoryPurchase()` (reuses `PurchaseOnAccountTemplate`/`CashPurchaseTemplate` with `$expenseAccount` set to `INVENTORY`) |
| 60 | Inventory Sale | Record COGS on sale | DR: `COST_OF_GOODS_SOLD`<br>CR: `INVENTORY` | ✅ `PostingService::costOfGoodsSold()` (reuses `InventoryAdjustmentTemplate` with `COST_OF_GOODS_SOLD` override) |
| 61 | Inventory Adjustment | Adjust for count discrepancy (increase) | DR: `INVENTORY`<br>CR: `INVENTORY_ADJUSTMENTS` | ✅ `InventoryAdjustmentTemplate` (`isIncrease: true`) |
| 62 | Inventory Return to Supplier | Return defective inventory | DR: `ACCOUNTS_PAYABLE` / `CASH`<br>CR: `INVENTORY` | ✅ `PurchaseReturnTemplate` / `return.purchase` |
| 63 | Inventory Write-off | Write off obsolete/damaged inventory | DR: `INVENTORY_ADJUSTMENTS`<br>CR: `INVENTORY` | ✅ `InventoryAdjustmentTemplate` (`isIncrease: false`) |
| 64 | Inventory Revaluation | Adjust to net realizable value (write-down) | DR: `INVENTORY_ADJUSTMENTS`<br>CR: `INVENTORY` | ✅ `InventoryAdjustmentTemplate` (`isIncrease: false`, same as row 63) |
| 65 | Inventory Transfer | Move between locations/warehouses | DR: `INVENTORY` (location B)<br>CR: `INVENTORY` (location A) | 🔧 plain 2-line entry between two distinct inventory accounts |
| 66 | Cash Purchase | Buy goods/services with cash | DR: `PURCHASES` / `OPERATING_EXPENSES`<br>CR: `CASH` | ✅ `CashPurchaseTemplate` / `cashPurchase()` (defaults to `COST_OF_GOODS_SOLD`, override `$expenseAccount` for a non-COGS purchase) |
| 67 | Purchase on Account | Buy on credit | DR: `PURCHASES` / `INVENTORY`<br>CR: `ACCOUNTS_PAYABLE` | ✅ `PurchaseOnAccountTemplate` / `purchaseOnAccount()` |
| 68 | Purchase Return | Return purchased goods | DR: `ACCOUNTS_PAYABLE`<br>CR: `PURCHASE_RETURNS` / `INVENTORY` | ✅ `PurchaseReturnTemplate` (same as row 62; defaults to crediting `INVENTORY`, override for `PURCHASE_RETURNS` under a periodic system) |
| 69 | Debit Memo Issue | Issue debit note to supplier | DR: `ACCOUNTS_PAYABLE`<br>CR: `PURCHASE_RETURNS` / `OPERATING_EXPENSES` | 🔧 `PurchaseReturnTemplate` with presets overridden |
| | **VII. ADJUSTING ENTRIES** | | | |
| 70 | Bad Debt Estimation | Estimate uncollectible accounts | DR: `BAD_DEBT_EXPENSE`<br>CR: `ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS` | ✅ `BadDebtTemplate` / registry key `debt.estimate` (estimation step only — see row 71 for the write-off) |
| 71 | Bad Debt Writeoff | Write off specific account | DR: `ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS`<br>CR: `ACCOUNTS_RECEIVABLE` | ✅ `BadDebtWriteoffTemplate` / `writeOffBadDebt()` - the `debt.writeoff` registry key now correctly points here instead of at the estimation template |
| 72 | Bad Debt Recovery | Collect previously written-off debt | DR: `CASH`<br>CR: `ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS` / `BAD_DEBT_EXPENSE` | ✅ `BadDebtRecoveryTemplate` / `recoverBadDebt()` - defaults to crediting the allowance; override `$allowanceAccount` to `BAD_DEBT_EXPENSE` for that alternative |
| 73 | Warranty Provision | Accrue estimated warranty costs | DR: `OTHER_EXPENSES`<br>CR: `WARRANTY_LIABILITIES` | 🔧 plain 2-line entry |
| 74 | Warranty Claim Payment | Pay actual warranty claim | DR: `WARRANTY_LIABILITIES`<br>CR: `CASH` / `INVENTORY` | 🔧 `BillPaymentTemplate` with presets overridden |
| 75 | Journal Entry Reversal | Reverse previous entry | Opposite of original entry | ✅ `JournalEntry::reverse()` / `JournalService::reverse()` — a core package feature, not a template |
| 76 | Prior Period Adjustment | Correct prior year error | DR/CR: `RETAINED_EARNINGS`<br>CR/DR: Affected Account | 🔧 plain 2-line entry |
| 77 | Rounding Adjustment | Adjust for rounding differences | DR/CR: `OTHER_EXPENSES`<br>CR/DR: Affected Account | 🔧 plain 2-line entry |
| | **VIII. CLOSING ENTRIES** | | | |
| 78 | Close Revenue | Close revenue to income summary | DR: `SALES_REVENUE` / Revenue Accounts<br>CR: `INCOME_SUMMARY` | ✅ `ClosingService::commitCloseRevenue()` / `commit()` |
| 79 | Close Expense | Close expenses to income summary | DR: `INCOME_SUMMARY`<br>CR: `OPERATING_EXPENSES` / Expense Accounts | ✅ `ClosingService::commitCloseExpenses()` / `commit()` |
| 80 | Close Income Summary (Profit) | Transfer profit to retained earnings | DR: `INCOME_SUMMARY`<br>CR: `RETAINED_EARNINGS` | ✅ `ClosingService::commitCloseIncomeSummary()` / `commit()` |
| 81 | Close Income Summary (Loss) | Transfer loss to retained earnings | DR: `RETAINED_EARNINGS`<br>CR: `INCOME_SUMMARY` | ✅ same method - handles profit or loss automatically based on Income Summary's actual net movement |
| 82 | Close Dividends | Close dividends to retained earnings | DR: `RETAINED_EARNINGS`<br>CR: `DIVIDENDS_DECLARED` / `DRAWINGS` | ✅ `ClosingService::commitCloseDividends()` / `commit()` - skipped automatically if nothing was declared |
| | **IX. SPECIALIZED TRANSACTIONS** | | | |
| 83 | Investment Purchase | Buy stocks/bonds | DR: `SHORT_TERM_INVESTMENTS` / `LONG_TERM_INVESTMENTS`<br>CR: `CASH` | 🔧 `FixedAssetPurchaseTemplate` with presets overridden |
| 84 | Investment Sale | Sell investment | DR: `CASH`<br>CR: `SHORT_TERM_INVESTMENTS`<br>CR: `GAIN_ON_SALE_OF_ASSETS` (or DR `LOSS_ON_SALE_OF_ASSETS`) | ✅ `InvestmentSaleGainTemplate` / `investmentSaleGain()` (gain) and `InvestmentSaleLossTemplate` / `investmentSaleLoss()` (loss) - `$costBasis` is caller-supplied, not queried |
| 85 | Investment Fair Value Gain | Mark-to-market gain | DR: `SHORT_TERM_INVESTMENTS`<br>CR: `UNREALIZED_GAIN` | 🔧 plain 2-line entry — no automated mark-to-market calculation |
| 86 | Investment Fair Value Loss | Mark-to-market loss | DR: `UNREALIZED_LOSS`<br>CR: `SHORT_TERM_INVESTMENTS` | 🔧 plain 2-line entry |
| 87 | Dividend Received | Receive dividend from investment | DR: `CASH`<br>CR: `DIVIDEND_REVENUE` | 🔧 `CashSaleTemplate` with `$revenueAccount` overridden, `taxRate: 0` |
| 88 | Interest Received | Receive interest income | DR: `CASH`<br>CR: `INTEREST_REVENUE` | 🔧 `CashSaleTemplate` with `$revenueAccount` overridden, `taxRate: 0` |
| 89 | Foreign Exchange Gain | Gain on currency conversion | DR: `CASH` / `ACCOUNTS_RECEIVABLE`<br>CR: `FOREIGN_EXCHANGE_GAIN` | ✅ `ForeignExchangeGainTemplate` / `foreignExchangeGain()` - posts an already-known gain amount; doesn't calculate it from exchange rates |
| 90 | Foreign Exchange Loss | Loss on currency conversion | DR: `FOREIGN_EXCHANGE_LOSS`<br>CR: `CASH` / `ACCOUNTS_PAYABLE` | ✅ `ForeignExchangeLossTemplate` / `foreignExchangeLoss()` - same caveat as row 89 |
| 91 | Intercompany Sale | Sale between related entities | DR: `OTHER_RECEIVABLES`<br>CR: `SALES_REVENUE` | 🔧 `CreditSaleTemplate` with `$receivableAccount` overridden |
| 92 | Intercompany Expense Allocation | Allocate shared costs | DR: `OPERATING_EXPENSES`<br>CR: `OTHER_PAYABLES` | 🔧 plain 2-line entry |
| | **X. BANK & CASH MANAGEMENT** | | | |
| 93 | Bank Deposit | Deposit undeposited funds | DR: `CASH_IN_BANK`<br>CR: `UNDEPOSITED_FUNDS` | 🔧 plain 2-line entry between two accounts already in the Cash and Cash Equivalents group |
| 94 | Bank Transfer | Transfer between bank accounts | DR: `CASH_IN_BANK` (Bank B)<br>CR: `CASH_IN_BANK` (Bank A) | 🔧 plain 2-line entry — only one account can carry the `CASH_IN_BANK` preset at a time, so a second bank account needs its own preset or manual account selection in the Journal Voucher UI |
| 95 | Bank Reconciliation Adjustment | Reconcile book to bank | DR/CR: `CASH`<br>CR/DR: `INTEREST_REVENUE` / `BANK_CHARGES` | 🔧 plain 2-line entry |
| 96 | Petty Cash Replenishment | Replenish petty cash | DR: `OPERATING_EXPENSES`<br>CR: `CASH` | 🔧 `CashPurchaseTemplate` with presets overridden |
| 97 | Cash Over/Short | Register discrepancies | DR: `OTHER_EXPENSES` (if short)<br>CR: `CASH` (or vice versa if over) | 🔧 plain 2-line entry |
| | **XI. TAX TRANSACTIONS** | | | |
| 98 | VAT Payment | Pay VAT to tax authority | DR: `OUTPUT_VAT`<br>CR: `INPUT_VAT`<br>CR: `CASH` (net payment) | ✅ `VatPaymentTemplate` / `vatPayment()` - `$model->getAmount()` is the Output VAT balance; `$inputVatAmount` is caller-supplied, not queried |
| 99 | Tax Refund | Receive tax refund | DR: `CASH`<br>CR: `INPUT_VAT` / `PREPAID_TAXES` | 🔧 plain 2-line entry |
| 100 | Withholding Tax Payment | Remit withheld taxes | DR: `WITHHOLDING_TAX_PAYABLE`<br>CR: `CASH` | 🔧 `BillPaymentTemplate` with `$payableAccount` overridden |
| 101 | Sales Tax Payment | Pay sales tax collected | DR: `SALES_TAX_PAYABLE`<br>CR: `CASH` | 🔧 `BillPaymentTemplate` with `$payableAccount` overridden |

> Row 102 ("Fixed Asset Purchase") was removed from the original doc: it was a verbatim duplicate of rows 11–12, and was misplaced under Tax Transactions.

## Known Gaps

Every transaction originally marked ❌ in this document has since been resolved — rows 3, 8, 14, 15, 27, 37, 52, 78–82, 84, 89, 90, and 98 are all ✅ now. What's genuinely left is a handful of 🔧 rows that are postable today but have no dedicated shortcut:

- **Two-step dividend** (rows 5–6: declare to a payable, then pay it) — only the one-step `DividendTemplate` exists.
- **Mark-to-market revaluation** (rows 85–86) — posting a known gain/loss is trivial (same shape as `ForeignExchangeGainTemplate`), but nothing in the package calculates the unrealized gain/loss itself from current fair value. Same for *unrealized* FX revaluation (as opposed to the *realized* FX gain/loss in rows 89–90, which is implemented) — both are a period-end, scan-every-open-balance batch process, closer in shape to `ClosingService` than a per-transaction template, and neither is built.
- **Sales return tax reversal** (row 26) — `SalesReturnTemplate` posts the return but has no line reversing the associated sales tax.

Everything else marked 🔧 is a plain debit/credit pair postable today, either by overriding an existing template's account parameters or by building the entry directly in the Journal Voucher UI — it's a missing *shortcut*, not a missing *capability*.
