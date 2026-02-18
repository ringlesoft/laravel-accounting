<?php

namespace RingleSoft\LaravelAccounting\Enums;

enum TransactionTemplate: string
{
    // I. EQUITY TRANSACTIONS
    case CAPITAL_CONTRIBUTION_CASH = 'CapitalContributionCash';
    case CAPITAL_CONTRIBUTION_NON_CASH = 'CapitalContributionNonCash';
    case STOCK_ISSUANCE = 'StockIssuance';
    case OWNER_WITHDRAWAL = 'OwnerWithdrawal';
    case DIVIDEND_DECLARATION = 'DividendDeclaration';
    case DIVIDEND_PAYMENT = 'DividendPayment';

    // II. ASSET TRANSACTIONS
    case ASSET_PURCHASE_CASH = 'AssetPurchaseCash';
    case ASSET_PURCHASE_CREDIT = 'AssetPurchaseCredit';
    case ASSET_PURCHASE_LOAN = 'AssetPurchaseLoan';
    case ASSET_SALE = 'AssetSale';
    case ASSET_DISPOSAL = 'AssetDisposal';
    case DEPRECIATION = 'Depreciation';
    case AMORTIZATION = 'Amortization';

    // III. REVENUE TRANSACTIONS
    case CASH_SALE = 'CashSale';
    case CREDIT_SALE = 'CreditSale';
    case ADVANCE_PAYMENT_RECEIPT = 'AdvancePaymentReceipt';
    case UNEARNED_REVENUE_RECOGNITION = 'UnearnedRevenueRecognition';
    case SALES_RETURN = 'SalesReturn';
    case SALES_DISCOUNT = 'SalesDiscount';

    // IV. EXPENSE TRANSACTIONS
    case EXPENSE_PAYMENT_CASH = 'ExpensePaymentCash';
    case EXPENSE_INCURRED_CREDIT = 'ExpenseIncurredCredit';
    case PREPAID_EXPENSE_PAYMENT = 'PrepaidExpensePayment';
    case PREPAID_EXPENSE_RECOGNITION = 'PrepaidExpenseRecognition';
    case PAYROLL_ACCRUAL = 'PayrollAccrual';
    case PAYROLL_PAYMENT = 'PayrollPayment';

    // V. LIABILITY TRANSACTIONS
    case LOAN_RECEIPT = 'LoanReceipt';
    case INTEREST_ACCRUAL = 'InterestAccrual';
    case BILL_PAYMENT = 'BillPayment';
    case LOAN_PAYMENT = 'LoanPayment';
    case ACCRUED_EXPENSE_PAYMENT = 'AccruedExpensePayment';

    // VI. INVENTORY & COGS
    case INVENTORY_PURCHASE = 'InventoryPurchase';
    case INVENTORY_SALE = 'InventorySale';
    case INVENTORY_ADJUSTMENT = 'InventoryAdjustment';

    // VII. ADJUSTING ENTRIES
    case ACCRUED_REVENUE = 'AccruedRevenue';
    case ACCRUED_EXPENSE = 'AccruedExpense';
    case SUPPLIES_USED = 'SuppliesUsed';
    case PREPAID_INSURANCE_EXPIRED = 'PrepaidInsuranceExpired';
    case BAD_DEBT_ESTIMATION = 'BadDebtEstimation';
    case BAD_DEBT_WRITEOFF = 'BadDebtWriteoff';

    // VIII. CLOSING ENTRIES
    case CLOSE_REVENUE = 'CloseRevenue';
    case CLOSE_EXPENSE = 'CloseExpense';
    case CLOSE_INCOME_SUMMARY_PROFIT = 'CloseIncomeSummaryProfit';
    case CLOSE_INCOME_SUMMARY_LOSS = 'CloseIncomeSummaryLoss';
    case CLOSE_DIVIDENDS = 'CloseDividends';

    // IX. SPECIALIZED TRANSACTIONS
    case INVESTMENT_PURCHASE = 'InvestmentPurchase';
    case INVESTMENT_FAIR_VALUE_GAIN = 'InvestmentFairValueGain';
    case INVESTMENT_FAIR_VALUE_LOSS = 'InvestmentFairValueLoss';
    case CAPITAL_LEASE = 'CapitalLease';
    case BOND_ISSUANCE = 'BondIssuance';
    case FOREIGN_EXCHANGE_GAIN = 'ForeignExchangeGain';
    case FOREIGN_EXCHANGE_LOSS = 'ForeignExchangeLoss';
    case ASSET_IMPAIRMENT = 'AssetImpairment';
    case DONATED_CAPITAL = 'DonatedCapital';

    // LEGACY (from original templates)
    case CASH_PURCHASE = 'CashPurchase';
    case PURCHASE_ON_ACCOUNT = 'PurchaseOnAccount';
    case PURCHASE_RETURN = 'PurchaseReturn';
    case PAYMENT_RECEIPT = 'PaymentReceipt';
    case FIXED_ASSET_PURCHASE = 'FixedAssetPurchase';

    /**
     * Get the AccountPreset and EntryType combinations for this template
     * Returns array with 'debit' and 'credit' keys containing AccountPreset enums or arrays of AccountPresets
     * When using byCategory(), returns array of arrays for group accounts
     */
    public function getEntries(): array
    {
        return match($this) {
            // I. EQUITY TRANSACTIONS
            self::CAPITAL_CONTRIBUTION_CASH => [
                'debit' => [AccountPreset::CASH],
                'credit' => [AccountPreset::OWNERS_EQUITY, AccountPreset::COMMON_STOCK],
            ],
            self::CAPITAL_CONTRIBUTION_NON_CASH => [
                'debit' => [AccountPreset::byCategory('fixed_assets')], // Group: Equipment, Land, Vehicle, etc.
                'credit' => [AccountPreset::OWNERS_EQUITY],
            ],
            self::STOCK_ISSUANCE => [
                'debit' => [AccountPreset::CASH],
                'credit' => [AccountPreset::COMMON_STOCK, AccountPreset::ADDITIONAL_PAID_IN_CAPITAL],
            ],
            self::OWNER_WITHDRAWAL => [
                'debit' => [AccountPreset::DRAWINGS, AccountPreset::DIVIDENDS_DECLARED],
                'credit' => [AccountPreset::CASH],
            ],
            self::DIVIDEND_DECLARATION => [
                'debit' => [AccountPreset::RETAINED_EARNINGS, AccountPreset::DIVIDENDS_DECLARED],
                'credit' => [AccountPreset::DIVIDENDS_DECLARED],
            ],
            self::DIVIDEND_PAYMENT => [
                'debit' => [AccountPreset::DIVIDENDS_DECLARED],
                'credit' => [AccountPreset::CASH],
            ],

            // II. ASSET TRANSACTIONS
            self::ASSET_PURCHASE_CASH => [
                'debit' => [AccountPreset::byCategory('fixed_assets')], // Group: Any asset account
                'credit' => [AccountPreset::CASH],
            ],
            self::ASSET_PURCHASE_CREDIT => [
                'debit' => [AccountPreset::byCategory('fixed_assets')], // Group: Any asset account
                'credit' => [AccountPreset::ACCOUNTS_PAYABLE],
            ],
            self::ASSET_PURCHASE_LOAN => [
                'debit' => [AccountPreset::byCategory('fixed_assets')], // Group: Any asset account
                'credit' => [AccountPreset::NOTES_PAYABLE, AccountPreset::LONG_TERM_DEBT],
            ],
            self::ASSET_SALE => [
                'debit' => [AccountPreset::CASH, AccountPreset::ACCUMULATED_DEPRECIATION],
                'credit' => [AccountPreset::byCategory('fixed_assets'), [AccountPreset::GAIN_ON_SALE_OF_ASSETS, AccountPreset::LOSS_ON_SALE_OF_ASSETS]],
            ],
            self::ASSET_DISPOSAL => [
                'debit' => [AccountPreset::ACCUMULATED_DEPRECIATION, AccountPreset::LOSS_ON_SALE_OF_ASSETS],
                'credit' => [AccountPreset::byCategory('fixed_assets')],
            ],
            self::DEPRECIATION => [
                'debit' => [AccountPreset::DEPRECIATION_EXPENSE],
                'credit' => [AccountPreset::ACCUMULATED_DEPRECIATION],
            ],
            self::AMORTIZATION => [
                'debit' => [AccountPreset::AMORTIZATION_EXPENSE],
                'credit' => [AccountPreset::INTANGIBLE_ASSETS, AccountPreset::ACCUMULATED_AMORTIZATION],
            ],

            // III. REVENUE TRANSACTIONS
            self::CASH_SALE => [
                'debit' => [AccountPreset::CASH],
                'credit' => [AccountPreset::byCategory('operating_revenue'), AccountPreset::SALES_TAX_PAYABLE],
            ],
            self::CREDIT_SALE => [
                'debit' => [AccountPreset::ACCOUNTS_RECEIVABLE],
                'credit' => [AccountPreset::byCategory('operating_revenue')],
            ],
            self::ADVANCE_PAYMENT_RECEIPT => [
                'debit' => [AccountPreset::CASH],
                'credit' => [AccountPreset::UNEARNED_REVENUE],
            ],
            self::UNEARNED_REVENUE_RECOGNITION => [
                'debit' => [AccountPreset::UNEARNED_REVENUE],
                'credit' => [AccountPreset::byCategory('operating_revenue')],
            ],
            self::SALES_RETURN => [
                'debit' => [AccountPreset::SALES_RETURNS],
                'credit' => [AccountPreset::ACCOUNTS_RECEIVABLE, AccountPreset::CASH],
            ],
            self::SALES_DISCOUNT => [
                'debit' => [AccountPreset::SALES_DISCOUNTS],
                'credit' => [AccountPreset::ACCOUNTS_RECEIVABLE],
            ],

            // IV. EXPENSE TRANSACTIONS
            self::EXPENSE_PAYMENT_CASH => [
                'debit' => [AccountPreset::byCategory('operating_expenses')], // Group: Rent, Utilities, Salaries, etc.
                'credit' => [AccountPreset::CASH],
            ],
            self::EXPENSE_INCURRED_CREDIT => [
                'debit' => [AccountPreset::byCategory('operating_expenses')],
                'credit' => [AccountPreset::ACCOUNTS_PAYABLE],
            ],
            self::PREPAID_EXPENSE_PAYMENT => [
                'debit' => [AccountPreset::PREPAID_EXPENSES],
                'credit' => [AccountPreset::CASH],
            ],
            self::PREPAID_EXPENSE_RECOGNITION => [
                'debit' => [AccountPreset::byCategory('operating_expenses')],
                'credit' => [AccountPreset::PREPAID_EXPENSES],
            ],
            self::PAYROLL_ACCRUAL => [
                'debit' => [AccountPreset::SALARIES_EXPENSE],
                'credit' => [AccountPreset::WAGES_PAYABLE, AccountPreset::PAYROLL_TAX_PAYABLE],
            ],
            self::PAYROLL_PAYMENT => [
                'debit' => [AccountPreset::WAGES_PAYABLE, AccountPreset::PAYROLL_TAX_EXPENSE],
                'credit' => [AccountPreset::CASH, AccountPreset::PAYROLL_TAX_PAYABLE],
            ],

            // V. LIABILITY TRANSACTIONS
            self::LOAN_RECEIPT => [
                'debit' => [AccountPreset::CASH],
                'credit' => [AccountPreset::NOTES_PAYABLE, AccountPreset::LONG_TERM_DEBT],
            ],
            self::INTEREST_ACCRUAL => [
                'debit' => [AccountPreset::INTEREST_EXPENSE],
                'credit' => [AccountPreset::ACCRUED_INTEREST],
            ],
            self::BILL_PAYMENT => [
                'debit' => [AccountPreset::ACCOUNTS_PAYABLE],
                'credit' => [AccountPreset::CASH],
            ],
            self::LOAN_PAYMENT => [
                'debit' => [AccountPreset::NOTES_PAYABLE, AccountPreset::INTEREST_EXPENSE, AccountPreset::ACCRUED_INTEREST],
                'credit' => [AccountPreset::CASH],
            ],
            self::ACCRUED_EXPENSE_PAYMENT => [
                'debit' => [AccountPreset::byCategory('current_liabilities')], // Group: Wages Payable, Taxes Payable, etc.
                'credit' => [AccountPreset::CASH],
            ],

            // VI. INVENTORY & COGS
            self::INVENTORY_PURCHASE => [
                'debit' => [AccountPreset::INVENTORY],
                'credit' => [AccountPreset::CASH, AccountPreset::ACCOUNTS_PAYABLE],
            ],
            self::INVENTORY_SALE => [
                'debit' => [AccountPreset::CASH, AccountPreset::ACCOUNTS_RECEIVABLE, AccountPreset::COST_OF_GOODS_SOLD],
                'credit' => [AccountPreset::SALES_REVENUE, AccountPreset::INVENTORY],
            ],
            self::INVENTORY_ADJUSTMENT => [
                'debit' => [AccountPreset::COST_OF_GOODS_SOLD, AccountPreset::INVENTORY],
                'credit' => [AccountPreset::INVENTORY, AccountPreset::PURCHASES],
            ],

            // VII. ADJUSTING ENTRIES
            self::ACCRUED_REVENUE => [
                'debit' => [AccountPreset::ACCOUNTS_RECEIVABLE],
                'credit' => [AccountPreset::byCategory('operating_revenue')],
            ],
            self::ACCRUED_EXPENSE => [
                'debit' => [AccountPreset::byCategory('operating_expenses')],
                'credit' => [AccountPreset::byCategory('current_liabilities')],
            ],
            self::SUPPLIES_USED => [
                'debit' => [AccountPreset::OFFICE_SUPPLIES_EXPENSE],
                'credit' => [AccountPreset::SUPPLIES],
            ],
            self::PREPAID_INSURANCE_EXPIRED => [
                'debit' => [AccountPreset::INSURANCE_EXPENSE],
                'credit' => [AccountPreset::PREPAID_INSURANCE],
            ],
            self::BAD_DEBT_ESTIMATION => [
                'debit' => [AccountPreset::BAD_DEBT_EXPENSE],
                'credit' => [AccountPreset::ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS],
            ],
            self::BAD_DEBT_WRITEOFF => [
                'debit' => [AccountPreset::ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS],
                'credit' => [AccountPreset::ACCOUNTS_RECEIVABLE],
            ],

            // VIII. CLOSING ENTRIES
            self::CLOSE_REVENUE => [
                'debit' => [AccountPreset::byCategory('operating_revenue')], // All revenue accounts
                'credit' => [AccountPreset::INCOME_SUMMARY],
            ],
            self::CLOSE_EXPENSE => [
                'debit' => [AccountPreset::INCOME_SUMMARY],
                'credit' => [AccountPreset::byCategory('operating_expenses')], // All expense accounts
            ],
            self::CLOSE_INCOME_SUMMARY_PROFIT => [
                'debit' => [AccountPreset::INCOME_SUMMARY],
                'credit' => [AccountPreset::RETAINED_EARNINGS],
            ],
            self::CLOSE_INCOME_SUMMARY_LOSS => [
                'debit' => [AccountPreset::RETAINED_EARNINGS],
                'credit' => [AccountPreset::INCOME_SUMMARY],
            ],
            self::CLOSE_DIVIDENDS => [
                'debit' => [AccountPreset::RETAINED_EARNINGS],
                'credit' => [AccountPreset::DRAWINGS, AccountPreset::DIVIDENDS_DECLARED],
            ],

            // IX. SPECIALIZED TRANSACTIONS
            self::INVESTMENT_PURCHASE => [
                'debit' => [AccountPreset::TRADING_SECURITIES, AccountPreset::MARKETABLE_SECURITIES],
                'credit' => [AccountPreset::CASH],
            ],
            self::INVESTMENT_FAIR_VALUE_GAIN => [
                'debit' => [AccountPreset::FAIR_VALUE_ADJUSTMENT],
                'credit' => [AccountPreset::UNREALIZED_GAIN],
            ],
            self::INVESTMENT_FAIR_VALUE_LOSS => [
                'debit' => [AccountPreset::UNREALIZED_LOSS],
                'credit' => [AccountPreset::FAIR_VALUE_ADJUSTMENT],
            ],
            self::CAPITAL_LEASE => [
                'debit' => [AccountPreset::LEASED_ASSET],
                'credit' => [AccountPreset::LEASE_LIABILITIES],
            ],
            self::BOND_ISSUANCE => [
                'debit' => [AccountPreset::CASH, AccountPreset::DISCOUNT_ON_BONDS, AccountPreset::PREMIUM_ON_BONDS],
                'credit' => [AccountPreset::BONDS_PAYABLE],
            ],
            self::FOREIGN_EXCHANGE_GAIN => [
                'debit' => [AccountPreset::ACCOUNTS_RECEIVABLE, AccountPreset::ACCOUNTS_PAYABLE],
                'credit' => [AccountPreset::FOREIGN_EXCHANGE_GAIN],
            ],
            self::FOREIGN_EXCHANGE_LOSS => [
                'debit' => [AccountPreset::FOREIGN_EXCHANGE_LOSS],
                'credit' => [AccountPreset::ACCOUNTS_RECEIVABLE, AccountPreset::ACCOUNTS_PAYABLE],
            ],
            self::ASSET_IMPAIRMENT => [
                'debit' => [AccountPreset::IMPAIRMENT_LOSS],
                'credit' => [AccountPreset::byCategory('fixed_assets'), AccountPreset::ACCUMULATED_IMPAIRMENT],
            ],
            self::DONATED_CAPITAL => [
                'debit' => [AccountPreset::byCategory('fixed_assets')], // Asset received
                'credit' => [AccountPreset::DONATED_CAPITAL],
            ],

            // LEGACY (from original templates)
            self::CASH_PURCHASE => [
                'debit' => [AccountPreset::COST_OF_GOODS_SOLD, AccountPreset::INPUT_VAT],
                'credit' => [AccountPreset::CASH],
            ],
            self::PURCHASE_ON_ACCOUNT => [
                'debit' => [AccountPreset::COST_OF_GOODS_SOLD, AccountPreset::INPUT_VAT],
                'credit' => [AccountPreset::ACCOUNTS_PAYABLE],
            ],
            self::PURCHASE_RETURN => [
                'debit' => [AccountPreset::ACCOUNTS_PAYABLE, AccountPreset::CASH],
                'credit' => [AccountPreset::INVENTORY],
            ],
            self::PAYMENT_RECEIPT => [
                'debit' => [AccountPreset::CASH],
                'credit' => [AccountPreset::ACCOUNTS_RECEIVABLE],
            ],
            self::FIXED_ASSET_PURCHASE => [
                'debit' => [AccountPreset::byCategory('fixed_assets')],
                'credit' => [AccountPreset::CASH, AccountPreset::ACCOUNTS_PAYABLE],
            ],
        };
    }

    /**
     * Get all debit AccountPresets for this template
     * Returns flat array, expanding any category groups
     */
    public function getDebitAccounts(): array
    {
        return $this->$this->getEntries()['debit'];
    }

    /**
     * Get all credit AccountPresets for this template
     * Returns flat array, expanding any category groups
     */
    public function getCreditAccounts(): array
    {
        return $this->$this->getEntries()['credit'];
    }

    /**
     * Get all AccountPresets used in this template (both debit and credit)
     * Returns flat array, expanding any category groups
     */
    public function getAllAccounts(): array
    {
        $entries = $this->getEntries();
        return array_merge(
            $this->flattenAccounts($entries['debit']),
            $this->flattenAccounts($entries['credit'])
        );
    }

    /**
     * Check if this template uses a specific AccountPreset
     */
    public function usesAccount(AccountPreset $account): bool
    {
        return in_array($account, $this->getAllAccounts(), true);
    }

    /**
     * Get the EntryType for a specific AccountPreset in this template
     */
    public function getEntryTypeForAccount(AccountPreset $account): ?EntryType
    {
        $entries = $this->getEntries();

        if (in_array($account, $this->flattenAccounts($entries['debit']), true)) {
            return EntryType::DEBIT;
        }

        if (in_array($account, $this->flattenAccounts($entries['credit']), true)) {
            return EntryType::CREDIT;
        }

        return null;
    }

    /**
     * Flatten accounts array, expanding any nested arrays from byCategory()
     *
     * @param array $accounts Array that may contain AccountPreset enums or arrays of AccountPresets
     * @return array Flat array of AccountPreset enums
     */
    private function flattenAccounts(array $accounts): array
    {
        $flattened = [];

        foreach ($accounts as $item) {
            if (is_array($item)) {
                // Recursively flatten nested arrays (from byCategory())
                $flattened = array_merge($flattened, $this->flattenAccounts($item));
            } else {
                $flattened[] = $item;
            }
        }

        return $flattened;
    }

    /**
     * Check if this template uses category groups (byCategory)
     */
    public function usesCategoryGroups(): bool
    {
        $entries = $this->getEntries();

        foreach (array_merge($entries['debit'], $entries['credit']) as $item) {
            if (is_array($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the categories used in this template
     *
     * @return array Array of category names used (e.g., ['fixed_assets', 'operating_revenue'])
     */
    public function getCategories(): array
    {
        $categories = [];
        $entries = $this->getEntries();

        // Check which categories are used by comparing with known categories
        $knownCategories = ['current_assets', 'fixed_assets', 'current_liabilities',
                           'operating_revenue', 'operating_expenses', 'tax_accounts'];

        foreach ($knownCategories as $category) {
            $categoryAccounts = AccountPreset::byCategory($category);
            $allAccounts = $this->getAllAccounts();

            // Check if any account from this category is used
            foreach ($categoryAccounts as $categoryAccount) {
                if (in_array($categoryAccount, $allAccounts, true)) {
                    $categories[] = $category;
                    break;
                }
            }
        }

        return array_unique($categories);
    }

    /**
     * Get human-readable description of this transaction template
     */
    public function getDescription(): string
    {
        return match($this) {
            // I. EQUITY TRANSACTIONS
            self::CAPITAL_CONTRIBUTION_CASH => 'Capital Contribution (Cash) - Owner invests cash into business',
            self::CAPITAL_CONTRIBUTION_NON_CASH => 'Capital Contribution (Non-Cash) - Owner contributes assets to business',
            self::STOCK_ISSUANCE => 'Stock Issuance - Company issues stock to shareholders',
            self::OWNER_WITHDRAWAL => 'Owner Withdrawal/Drawings - Owner withdraws cash from business',
            self::DIVIDEND_DECLARATION => 'Dividend Declaration - Company declares dividends to shareholders',
            self::DIVIDEND_PAYMENT => 'Dividend Payment - Company pays declared dividends',

            // II. ASSET TRANSACTIONS
            self::ASSET_PURCHASE_CASH => 'Asset Purchase (Cash) - Purchase asset with cash payment',
            self::ASSET_PURCHASE_CREDIT => 'Asset Purchase (Credit) - Purchase asset on account',
            self::ASSET_PURCHASE_LOAN => 'Asset Purchase (Loan) - Purchase asset financed by loan',
            self::ASSET_SALE => 'Asset Sale - Sell asset for cash with gain/loss recognition',
            self::ASSET_DISPOSAL => 'Asset Disposal - Discard or dispose of asset',
            self::DEPRECIATION => 'Depreciation - Record periodic depreciation expense',
            self::AMORTIZATION => 'Amortization - Record periodic amortization of intangible assets',

            // III. REVENUE TRANSACTIONS
            self::CASH_SALE => 'Cash Sale - Sell product/service for immediate cash payment',
            self::CREDIT_SALE => 'Credit Sale - Sell product/service on account',
            self::ADVANCE_PAYMENT_RECEIPT => 'Advance Payment - Receive payment before delivering goods/services',
            self::UNEARNED_REVENUE_RECOGNITION => 'Revenue Recognition - Recognize previously unearned revenue',
            self::SALES_RETURN => 'Sales Return - Customer returns product for refund',
            self::SALES_DISCOUNT => 'Sales Discount - Customer takes early payment discount',

            // IV. EXPENSE TRANSACTIONS
            self::EXPENSE_PAYMENT_CASH => 'Expense Payment (Cash) - Pay operating expense with cash',
            self::EXPENSE_INCURRED_CREDIT => 'Expense Incurred (Credit) - Incur expense on account',
            self::PREPAID_EXPENSE_PAYMENT => 'Prepaid Expense Payment - Pay for expense in advance',
            self::PREPAID_EXPENSE_RECOGNITION => 'Prepaid Expense Recognition - Recognize portion of prepaid expense',
            self::PAYROLL_ACCRUAL => 'Payroll Accrual - Accrue employee wages and taxes',
            self::PAYROLL_PAYMENT => 'Payroll Payment - Pay employee wages',

            // V. LIABILITY TRANSACTIONS
            self::LOAN_RECEIPT => 'Loan Receipt - Receive loan proceeds',
            self::INTEREST_ACCRUAL => 'Interest Accrual - Accrue interest expense on loan',
            self::BILL_PAYMENT => 'Bill Payment - Pay accounts payable',
            self::LOAN_PAYMENT => 'Loan Payment - Make loan payment (principal + interest)',
            self::ACCRUED_EXPENSE_PAYMENT => 'Accrued Expense Payment - Pay previously accrued expense',

            // VI. INVENTORY & COGS
            self::INVENTORY_PURCHASE => 'Inventory Purchase - Purchase inventory for resale',
            self::INVENTORY_SALE => 'Inventory Sale - Sell inventory and record COGS',
            self::INVENTORY_ADJUSTMENT => 'Inventory Adjustment - Adjust inventory for shrinkage or corrections',

            // VII. ADJUSTING ENTRIES
            self::ACCRUED_REVENUE => 'Accrued Revenue - Record revenue earned but not yet received',
            self::ACCRUED_EXPENSE => 'Accrued Expense - Record expense incurred but not yet paid',
            self::SUPPLIES_USED => 'Supplies Used - Recognize supplies consumed during period',
            self::PREPAID_INSURANCE_EXPIRED => 'Prepaid Insurance Expired - Recognize expired insurance',
            self::BAD_DEBT_ESTIMATION => 'Bad Debt Estimation - Estimate uncollectible accounts',
            self::BAD_DEBT_WRITEOFF => 'Bad Debt Write-off - Write off specific uncollectible account',

            // VIII. CLOSING ENTRIES
            self::CLOSE_REVENUE => 'Close Revenue - Transfer revenue accounts to income summary',
            self::CLOSE_EXPENSE => 'Close Expense - Transfer expense accounts to income summary',
            self::CLOSE_INCOME_SUMMARY_PROFIT => 'Close Income Summary (Profit) - Transfer net income to retained earnings',
            self::CLOSE_INCOME_SUMMARY_LOSS => 'Close Income Summary (Loss) - Transfer net loss to retained earnings',
            self::CLOSE_DIVIDENDS => 'Close Dividends - Transfer dividends to retained earnings',

            // IX. SPECIALIZED TRANSACTIONS
            self::INVESTMENT_PURCHASE => 'Investment Purchase - Purchase trading securities',
            self::INVESTMENT_FAIR_VALUE_GAIN => 'Investment Fair Value Gain - Record unrealized gain on investments',
            self::INVESTMENT_FAIR_VALUE_LOSS => 'Investment Fair Value Loss - Record unrealized loss on investments',
            self::CAPITAL_LEASE => 'Capital Lease - Record capital lease asset and liability',
            self::BOND_ISSUANCE => 'Bond Issuance - Issue bonds payable',
            self::FOREIGN_EXCHANGE_GAIN => 'Foreign Exchange Gain - Record gain from currency fluctuation',
            self::FOREIGN_EXCHANGE_LOSS => 'Foreign Exchange Loss - Record loss from currency fluctuation',
            self::ASSET_IMPAIRMENT => 'Asset Impairment - Record impairment loss on asset',
            self::DONATED_CAPITAL => 'Donated Capital - Record donated assets',

            // LEGACY
            self::CASH_PURCHASE => 'Cash Purchase - Purchase inventory/expense with cash',
            self::PURCHASE_ON_ACCOUNT => 'Purchase on Account - Purchase inventory/expense on credit',
            self::PURCHASE_RETURN => 'Purchase Return - Return purchased goods',
            self::PAYMENT_RECEIPT => 'Payment Receipt - Receive payment from customer',
            self::FIXED_ASSET_PURCHASE => 'Fixed Asset Purchase - Purchase fixed asset',
        };
    }
}
