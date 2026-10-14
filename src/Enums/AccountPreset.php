<?php

namespace RingleSoft\LaravelAccounting\Enums;

/**
 * Standard Chart of Accounts Presets
 * Based on IFRS/US GAAP with common industry variations
 */
enum AccountPreset: string
{
    // ====================================
    // ASSETS (1xxx - 2xxx series)
    // ====================================

    // Current Assets (1000-1499)
    case CASH = 'CASH';
    case PETTY_CASH = 'PETTY_CASH';
    case CASH_ON_HAND = 'CASH_ON_HAND';
    case CASH_IN_BANK = 'CASH_IN_BANK';
    case CASH_AND_CASH_EQUIVALENT = 'CASH_AND_CASH_EQUIVALENT';
    case UNDEPOSITED_FUNDS = 'UNDEPOSITED_FUNDS';

    case ACCOUNTS_RECEIVABLE = 'ACCOUNTS_RECEIVABLE';
    case TRADE_RECEIVABLES = 'TRADE_RECEIVABLES';
    case NOTES_RECEIVABLE = 'NOTES_RECEIVABLE';
    case OTHER_RECEIVABLES = 'OTHER_RECEIVABLES';
    case UNBILLED_REVENUE = 'UNBILLED_REVENUE';
    case ACCRUED_REVENUE = 'ACCRUED_REVENUE';

    case ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS = 'ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS';
    case ALLOWANCE_FOR_BAD_DEBTS = 'ALLOWANCE_FOR_BAD_DEBTS';

    case INVENTORY = 'INVENTORY';
    case RAW_MATERIALS = 'RAW_MATERIALS';
    case WORK_IN_PROGRESS = 'WORK_IN_PROGRESS';
    case FINISHED_GOODS = 'FINISHED_GOODS';
    case MERCHANDISE_INVENTORY = 'MERCHANDISE_INVENTORY';

    case PREPAID_EXPENSES = 'PREPAID_EXPENSES';
    case PREPAID_INSURANCE = 'PREPAID_INSURANCE';
    case PREPAID_RENT = 'PREPAID_RENT';
    case PREPAID_SUBSCRIPTIONS = 'PREPAID_SUBSCRIPTIONS';

    case SUPPLIES = 'SUPPLIES';

    case PREPAID_TAXES = 'PREPAID_TAXES';
    case PREPAID_INCOME_TAX = 'PREPAID_INCOME_TAX';
    case ADVANCE_TO_SUPPLIERS = 'ADVANCE_TO_SUPPLIERS';
    case ADVANCE_TO_EMPLOYEES = 'ADVANCE_TO_EMPLOYEES';

    // Short-term Investments (1500-1599)
    case MARKETABLE_SECURITIES = 'MARKETABLE_SECURITIES';
    case SHORT_TERM_INVESTMENTS = 'SHORT_TERM_INVESTMENTS';
    case TRADING_SECURITIES = 'TRADING_SECURITIES';
    case FAIR_VALUE_ADJUSTMENT = 'FAIR_VALUE_ADJUSTMENT';
    case DIGITAL_ASSETS = 'DIGITAL_ASSETS';

    // Non-Current Assets (1600-1999)
    case LONG_TERM_INVESTMENTS = 'LONG_TERM_INVESTMENTS';
    case HELD_TO_MATURITY_SECURITIES = 'HELD_TO_MATURITY_SECURITIES';
    case AVAILABLE_FOR_SALE_SECURITIES = 'AVAILABLE_FOR_SALE_SECURITIES';

    // Property, Plant & Equipment (2000-2499)
    case LAND = 'LAND';
    case BUILDINGS = 'BUILDINGS';
    case BUILDING_IMPROVEMENTS = 'BUILDING_IMPROVEMENTS';
    case LEASEHOLD_IMPROVEMENTS = 'LEASEHOLD_IMPROVEMENTS';
    case CONSTRUCTION_IN_PROGRESS = 'CONSTRUCTION_IN_PROGRESS';

    case MACHINERY_AND_EQUIPMENT = 'MACHINERY_AND_EQUIPMENT';
    case OFFICE_EQUIPMENT = 'OFFICE_EQUIPMENT';
    case COMPUTER_EQUIPMENT = 'COMPUTER_EQUIPMENT';
    case FURNITURE_AND_FIXTURES = 'FURNITURE_AND_FIXTURES';
    case VEHICLES = 'VEHICLES';
    case LEASED_ASSET = 'LEASED_ASSET';

    // Accumulated Depreciation (Contra-Asset) (2500-2799)
    case ACCUMULATED_DEPRECIATION = 'ACCUMULATED_DEPRECIATION';
    case ACCUM_DEPR_BUILDINGS = 'ACCUM_DEPR_BUILDINGS';
    case ACCUM_DEPR_EQUIPMENT = 'ACCUM_DEPR_EQUIPMENT';
    case ACCUM_DEPR_VEHICLES = 'ACCUM_DEPR_VEHICLES';
    case ACCUM_DEPR_FURNITURE = 'ACCUM_DEPR_FURNITURE';
    case ACCUM_DEPR_OFFICE_EQUIPMENT = 'ACCUM_DEPR_OFFICE_EQUIPMENT';
    case ACCUM_DEPR_IT_EQUIPMENT = 'ACCUM_DEPR_IT_EQUIPMENT';
    case ACCUM_DEPR_LEASEHOLD = 'ACCUM_DEPR_LEASEHOLD';
    case ACCUMULATED_IMPAIRMENT = 'ACCUMULATED_IMPAIRMENT';

    // Intangible Assets (2800-2899)
    case INTANGIBLE_ASSETS = 'INTANGIBLE_ASSETS';
    case GOODWILL = 'GOODWILL';
    case PATENTS = 'PATENTS';
    case COPYRIGHTS = 'COPYRIGHTS';
    case TRADEMARKS = 'TRADEMARKS';
    case FRANCHISE_RIGHTS = 'FRANCHISE_RIGHTS';
    case SOFTWARE = 'SOFTWARE';

    case ACCUMULATED_AMORTIZATION = 'ACCUMULATED_AMORTIZATION';
    case ACCUM_AMORT_PATENTS = 'ACCUM_AMORT_PATENTS';
    case ACCUM_AMORT_SOFTWARE = 'ACCUM_AMORT_SOFTWARE';
    case ACCUM_AMORT_TRADEMARKS = 'ACCUM_AMORT_TRADEMARKS';
    case ACCUM_AMORT_COPYRIGHTS = 'ACCUM_AMORT_COPYRIGHTS';

    // Other Assets (2900-2999)
    case DEFERRED_TAX_ASSETS = 'DEFERRED_TAX_ASSETS';
    case RIGHT_OF_USE_ASSETS = 'RIGHT_OF_USE_ASSETS';
    case BOND_ISSUANCE_COSTS = 'BOND_ISSUANCE_COSTS';
    case DEPOSITS = 'DEPOSITS';
    case SECURITY_DEPOSITS = 'SECURITY_DEPOSITS';

    // ====================================
    // LIABILITIES (3xxx - 4xxx series)
    // ====================================

    // Current Liabilities (3000-3499)
    case ACCOUNTS_PAYABLE = 'ACCOUNTS_PAYABLE';
    case TRADE_PAYABLES = 'TRADE_PAYABLES';
    case NOTES_PAYABLE = 'NOTES_PAYABLE';
    case OTHER_PAYABLES = 'OTHER_PAYABLES';

    case ACCRUED_EXPENSES = 'ACCRUED_EXPENSES';
    case ACCRUED_SALARIES = 'ACCRUED_SALARIES';
    case ACCRUED_INTEREST = 'ACCRUED_INTEREST';
    case ACCRUED_UTILITIES = 'ACCRUED_UTILITIES';
    case ACCRUED_TAXES = 'ACCRUED_TAXES';
    case DIVIDENDS_PAYABLE = 'DIVIDENDS_PAYABLE';
    case LEASE_LIABILITY_CURRENT = 'LEASE_LIABILITY_CURRENT';

    case SHORT_TERM_LOANS = 'SHORT_TERM_LOANS';
    case CURRENT_PORTION_OF_LONG_TERM_DEBT = 'CURRENT_PORTION_OF_LONG_TERM_DEBT';
    case LINE_OF_CREDIT = 'LINE_OF_CREDIT';
    case BANK_OVERDRAFT = 'BANK_OVERDRAFT';

    case UNEARNED_REVENUE = 'UNEARNED_REVENUE';
    case DEFERRED_REVENUE = 'DEFERRED_REVENUE';
    case CUSTOMER_DEPOSITS = 'CUSTOMER_DEPOSITS';
    case ADVANCES_FROM_CUSTOMERS = 'ADVANCES_FROM_CUSTOMERS';

    // Tax Liabilities (3500-3599)
    case SALES_TAX_PAYABLE = 'SALES_TAX_PAYABLE';
    case VAT_PAYABLE = 'VAT_PAYABLE';
    case GST_PAYABLE = 'GST_PAYABLE';
    case INCOME_TAX_PAYABLE = 'INCOME_TAX_PAYABLE';
    case PAYROLL_TAX_PAYABLE = 'PAYROLL_TAX_PAYABLE';

    // Payroll Liabilities (3600-3699)
    case WAGES_PAYABLE = 'WAGES_PAYABLE';
    case SALARIES_PAYABLE = 'SALARIES_PAYABLE';
    case BONUSES_PAYABLE = 'BONUSES_PAYABLE';
    case COMMISSIONS_PAYABLE = 'COMMISSIONS_PAYABLE';
    case PAYROLL_CLEARING = 'PAYROLL_CLEARING';
    case FEDERAL_WITHHOLDING_PAYABLE = 'FEDERAL_WITHHOLDING_PAYABLE';
    case STATE_WITHHOLDING_PAYABLE = 'STATE_WITHHOLDING_PAYABLE';
    case SOCIAL_SECURITY_PAYABLE = 'SOCIAL_SECURITY_PAYABLE';
    case MEDICARE_PAYABLE = 'MEDICARE_PAYABLE';
    case VACATION_PAYABLE = 'VACATION_PAYABLE';

    case EMPLOYEE_BENEFITS_PAYABLE = 'EMPLOYEE_BENEFITS_PAYABLE';
    case EMPLOYEE_INSURANCE_PAYABLE = 'EMPLOYEE_INSURANCE_PAYABLE';
    case PENSION_LIABILITIES = 'PENSION_LIABILITIES';
    case EMPLOYEE_PROVIDENT_FUND = 'EMPLOYEE_PROVIDENT_FUND';

    // Long-term Liabilities (3700-3999)
    case LONG_TERM_DEBT = 'LONG_TERM_DEBT';
    case BONDS_PAYABLE = 'BONDS_PAYABLE';
    case DISCOUNT_ON_BONDS = 'DISCOUNT_ON_BONDS';
    case PREMIUM_ON_BONDS = 'PREMIUM_ON_BONDS';
    case MORTGAGE_PAYABLE = 'MORTGAGE_PAYABLE';
    case LEASE_LIABILITIES = 'LEASE_LIABILITIES';

    case DEFERRED_TAX_LIABILITIES = 'DEFERRED_TAX_LIABILITIES';
    case WARRANTY_LIABILITIES = 'WARRANTY_LIABILITIES';

    // ====================================
    // EQUITY (5xxx series)
    // ====================================

    case COMMON_STOCK = 'COMMON_STOCK';
    case PREFERRED_STOCK = 'PREFERRED_STOCK';
    case ADDITIONAL_PAID_IN_CAPITAL = 'ADDITIONAL_PAID_IN_CAPITAL';
    case TREASURY_STOCK = 'TREASURY_STOCK';

    case RETAINED_EARNINGS = 'RETAINED_EARNINGS';
    case CURRENT_YEAR_EARNINGS = 'CURRENT_YEAR_EARNINGS';
    case PRIOR_YEAR_RETAINED_EARNINGS = 'PRIOR_YEAR_RETAINED_EARNINGS';

    case OWNERS_EQUITY = 'OWNERS_EQUITY';
    case PARTNERS_EQUITY = 'PARTNERS_EQUITY';
    case MEMBERS_EQUITY = 'MEMBERS_EQUITY';

    case DRAWINGS = 'DRAWINGS';
    case OWNERS_DRAW = 'OWNERS_DRAW';
    case DIVIDENDS_DECLARED = 'DIVIDENDS_DECLARED';

    case INCOME_SUMMARY = 'INCOME_SUMMARY';
    case DONATED_CAPITAL = 'DONATED_CAPITAL';
    case OPENING_BALANCE_EQUITY = 'OPENING_BALANCE_EQUITY';

    case OTHER_COMPREHENSIVE_INCOME = 'OTHER_COMPREHENSIVE_INCOME';
    case REVALUATION_RESERVE = 'REVALUATION_RESERVE';
    case FOREIGN_CURRENCY_TRANSLATION = 'FOREIGN_CURRENCY_TRANSLATION';

    // ====================================
    // REVENUE (6xxx series)
    // ====================================

    // Operating Revenue (6000-6499)
    case SALES_REVENUE = 'SALES_REVENUE';
    case SERVICE_REVENUE = 'SERVICE_REVENUE';
    case CONSULTING_REVENUE = 'CONSULTING_REVENUE';
    case MAINTENANCE_REVENUE = 'MAINTENANCE_REVENUE';
    case SHIPPING_REVENUE = 'SHIPPING_REVENUE';

    case PRODUCT_SALES = 'PRODUCT_SALES';
    case SOFTWARE_SALES = 'SOFTWARE_SALES';
    case MERCHANDISE_SALES = 'MERCHANDISE_SALES';

    case RENTAL_REVENUE = 'RENTAL_REVENUE';
    case ROYALTY_REVENUE = 'ROYALTY_REVENUE';
    case FRANCHISE_FEES = 'FRANCHISE_FEES';
    case LICENSE_REVENUE = 'LICENSE_REVENUE';

    // Other Revenue (6500-6799)
    case INTEREST_REVENUE = 'INTEREST_REVENUE';
    case DIVIDEND_REVENUE = 'DIVIDEND_REVENUE';
    case INVESTMENT_INCOME = 'INVESTMENT_INCOME';

    case GAIN_ON_SALE_OF_ASSETS = 'GAIN_ON_SALE_OF_ASSETS';
    case UNREALIZED_GAIN = 'UNREALIZED_GAIN';
    case FOREIGN_EXCHANGE_GAIN = 'FOREIGN_EXCHANGE_GAIN';

    case OTHER_INCOME = 'OTHER_INCOME';
    case SUBSIDY_INCOME = 'SUBSIDY_INCOME';
    case GRANT_INCOME = 'GRANT_INCOME';

    // Contra-Revenue (6800-6899)
    case SALES_RETURNS = 'SALES_RETURNS';
    case SALES_ALLOWANCES = 'SALES_ALLOWANCES';
    case SALES_DISCOUNTS = 'SALES_DISCOUNTS';
    case PURCHASE_RETURNS = 'PURCHASE_RETURNS';
    case PURCHASE_DISCOUNTS = 'PURCHASE_DISCOUNTS';

    // ====================================
    // EXPENSES (7xxx - 8xxx series)
    // ====================================

    // Cost of Goods Sold (7000-7499)
    case COST_OF_GOODS_SOLD = 'COST_OF_GOODS_SOLD';
    case COST_OF_SALES = 'COST_OF_SALES';
    case COST_OF_SERVICES = 'COST_OF_SERVICES';
    case PURCHASES = 'PURCHASES';

    case MATERIALS_USED = 'MATERIALS_USED';
    case DIRECT_MATERIALS = 'DIRECT_MATERIALS';
    case DIRECT_LABOR = 'DIRECT_LABOR';
    case SUBCONTRACTOR_COSTS = 'SUBCONTRACTOR_COSTS';
    case FREIGHT_IN = 'FREIGHT_IN';
    case INVENTORY_ADJUSTMENTS = 'INVENTORY_ADJUSTMENTS';
    case MANUFACTURING_OVERHEAD = 'MANUFACTURING_OVERHEAD';

    // Operating Expenses (7500-7999)
    case SALARIES_EXPENSE = 'SALARIES_EXPENSE';
    case WAGES_EXPENSE = 'WAGES_EXPENSE';
    case BONUS_EXPENSE = 'BONUS_EXPENSE';
    case COMMISSION_EXPENSE = 'COMMISSION_EXPENSE';
    case PAYROLL_TAX_EXPENSE = 'PAYROLL_TAX_EXPENSE';
    case EMPLOYEE_BENEFITS_EXPENSE = 'EMPLOYEE_BENEFITS_EXPENSE';

    case OFFICE_EXPENSES = 'OFFICE_EXPENSES';
    case RENT_EXPENSE = 'RENT_EXPENSE';
    case LEASE_EXPENSE = 'LEASE_EXPENSE';
    case UTILITIES_EXPENSE = 'UTILITIES_EXPENSE';
    case OCCUPANCY_EXPENSES = 'OCCUPANCY_EXPENSES';

    case OFFICE_SUPPLIES_EXPENSE = 'OFFICE_SUPPLIES_EXPENSE';
    case POSTAGE_EXPENSE = 'POSTAGE_EXPENSE';
    case PRINTING_EXPENSE = 'PRINTING_EXPENSE';
    case TELEPHONE_EXPENSE = 'TELEPHONE_EXPENSE';
    case INTERNET_EXPENSE = 'INTERNET_EXPENSE';
    case SHIPPING_EXPENSE = 'SHIPPING_EXPENSE';
    case TECHNOLOGY_EXPENSE = 'TECHNOLOGY_EXPENSE';
    case LICENSES_AND_PERMITS = 'LICENSES_AND_PERMITS';
    case PROPERTY_TAX_EXPENSE = 'PROPERTY_TAX_EXPENSE';
    case MERCHANT_SERVICES_FEES = 'MERCHANT_SERVICES_FEES';
    case PAYROLL_PROCESSING_FEES = 'PAYROLL_PROCESSING_FEES';

    case TRAVEL_EXPENSE = 'TRAVEL_EXPENSE';
    case MEALS_AND_ENTERTAINMENT = 'MEALS_AND_ENTERTAINMENT';
    case VEHICLE_EXPENSE = 'VEHICLE_EXPENSE';

    case ADVERTISING_EXPENSE = 'ADVERTISING_EXPENSE';
    case MARKETING_EXPENSE = 'MARKETING_EXPENSE';
    case MARKETING_SALES_EXPENSES = 'MARKETING_SALES_EXPENSES';
    case PROMOTION_EXPENSE = 'PROMOTION_EXPENSE';

    case PROFESSIONAL_FEES = 'PROFESSIONAL_FEES';
    case LEGAL_FEES = 'LEGAL_FEES';
    case ACCOUNTING_FEES = 'ACCOUNTING_FEES';
    case CONSULTING_FEES = 'CONSULTING_FEES';

    case INSURANCE_EXPENSE = 'INSURANCE_EXPENSE';
    case REPAIRS_AND_MAINTENANCE = 'REPAIRS_AND_MAINTENANCE';

    case DEPRECIATION_EXPENSE = 'DEPRECIATION_EXPENSE';
    case AMORTIZATION_EXPENSE = 'AMORTIZATION_EXPENSE';
    case DEPLETION_EXPENSE = 'DEPLETION_EXPENSE';

    // Financial Expenses (8000-8199)
    case FINANCIAL_EXPENSES = 'FINANCIAL_EXPENSES';
    case INTEREST_EXPENSE = 'INTEREST_EXPENSE';
    case BANK_CHARGES = 'BANK_CHARGES';
    case CREDIT_CARD_FEES = 'CREDIT_CARD_FEES';
    case LOAN_FEES = 'LOAN_FEES';

    case FOREIGN_EXCHANGE_LOSS = 'FOREIGN_EXCHANGE_LOSS';
    case LOSS_ON_SALE_OF_ASSETS = 'LOSS_ON_SALE_OF_ASSETS';
    case UNREALIZED_LOSS = 'UNREALIZED_LOSS';
    case IMPAIRMENT_LOSS = 'IMPAIRMENT_LOSS';

    // Tax Expenses (8200-8299)
    case INCOME_TAX_EXPENSE = 'INCOME_TAX_EXPENSE';
    case DEFERRED_TAX_EXPENSE = 'DEFERRED_TAX_EXPENSE';

    // Other Expenses (8300-8999)
    case BAD_DEBT_EXPENSE = 'BAD_DEBT_EXPENSE';
    case DONATIONS = 'DONATIONS';
    case PENALTIES_AND_FINES = 'PENALTIES_AND_FINES';
    case OTHER_EXPENSES = 'OTHER_EXPENSES';

    case RESEARCH_AND_DEVELOPMENT = 'RESEARCH_AND_DEVELOPMENT';
    case TRAINING_EXPENSE = 'TRAINING_EXPENSE';
    case SUBSCRIPTION_EXPENSE = 'SUBSCRIPTION_EXPENSE';
    case DUES_AND_MEMBERSHIPS = 'DUES_AND_MEMBERSHIPS';
    case TAXES_AND_LICENSES = 'TAXES_AND_LICENSES';

    // ====================================
    // TAX ACCOUNTS (Special)
    // ====================================
    case INPUT_VAT = 'INPUT_VAT';
    case OUTPUT_VAT = 'OUTPUT_VAT';
    case VAT_RECOVERABLE = 'VAT_RECOVERABLE';

    case INPUT_GST = 'INPUT_GST';
    case OUTPUT_GST = 'OUTPUT_GST';
    case GST_RECOVERABLE = 'GST_RECOVERABLE';

    case WITHHOLDING_TAX_RECEIVABLE = 'WITHHOLDING_TAX_RECEIVABLE';
    case WITHHOLDING_TAX_PAYABLE = 'WITHHOLDING_TAX_PAYABLE';

    // ====================================
    // CLEARING & SUSPENSE ACCOUNTS
    // ====================================
    case SUSPENSE_ACCOUNT = 'SUSPENSE_ACCOUNT';
    case CLEARING_ACCOUNT = 'CLEARING_ACCOUNT';
    case INTERCOMPANY = 'INTERCOMPANY';
    case FIXED_ASSETS = 'FIXED_ASSETS';
    case OPERATING_EXPENSES = 'OPERATING_EXPENSES'; // Parent
    case EXPENSES = 'EXPENSES'; // Legacy alias
    case PAYROLL_EXPENSES = 'PAYROLL_EXPENSES'; // Parent
    case LOAN_PAYABLE = 'LOAN_PAYABLE';

    /**
     * Get account type (Asset, Liability, Equity, Revenue, Expense)
     */
    public function type(): AccountTypeEnum
    {
        return match ($this) {
            // Assets (1000-2999)
            self::CASH, self::ACCOUNTS_RECEIVABLE, self::INVENTORY,
            self::PREPAID_EXPENSES, self::LAND, self::BUILDINGS,
            self::MACHINERY_AND_EQUIPMENT, self::GOODWILL,
            self::INPUT_VAT, self::VAT_RECOVERABLE,
            self::WITHHOLDING_TAX_RECEIVABLE, self::FIXED_ASSETS,
            self::ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS,
            self::SUPPLIES, self::INTANGIBLE_ASSETS, self::LEASED_ASSET,
            self::FAIR_VALUE_ADJUSTMENT, self::ACCUMULATED_IMPAIRMENT => AccountTypeEnum::ASSET,

            // Liabilities (3000-4999)
            self::ACCOUNTS_PAYABLE, self::SALES_TAX_PAYABLE,
            self::VAT_PAYABLE, self::GST_PAYABLE,
            self::WAGES_PAYABLE, self::UNEARNED_REVENUE,
            self::LONG_TERM_DEBT, self::WITHHOLDING_TAX_PAYABLE,
            self::DISCOUNT_ON_BONDS, self::PREMIUM_ON_BONDS => AccountTypeEnum::LIABILITY,

            // Equity (5000-5999)
            self::COMMON_STOCK, self::RETAINED_EARNINGS,
            self::OWNERS_EQUITY, self::DRAWINGS,
            self::INCOME_SUMMARY, self::DONATED_CAPITAL => AccountTypeEnum::EQUITY,

            // Revenue (6000-6999)
            self::SALES_REVENUE, self::SERVICE_REVENUE,
            self::INTEREST_REVENUE, self::OTHER_INCOME,
            self::SALES_RETURNS, self::SALES_DISCOUNTS,
            self::UNREALIZED_GAIN => AccountTypeEnum::INCOME,

            // Expenses (7000-8999)
            self::EXPENSES,
            self::PAYROLL_EXPENSES,
            self::COST_OF_GOODS_SOLD, self::SALARIES_EXPENSE,
            self::RENT_EXPENSE, self::DEPRECIATION_EXPENSE,
            self::INTEREST_EXPENSE, self::BAD_DEBT_EXPENSE,
            self::INPUT_GST, self::OUTPUT_GST,
            self::PURCHASES, self::PAYROLL_TAX_EXPENSE,
            self::UNREALIZED_LOSS, self::IMPAIRMENT_LOSS => AccountTypeEnum::EXPENSE,

            default => AccountTypeEnum::ASSET, // Fallback
        };
    }

    /**
     * Get recommended normal balance (debit/credit)
     */
    public function normalBalance(): NormalBalance
    {
        return match ($this->type()) {
            AccountTypeEnum::ASSET, AccountTypeEnum::EXPENSE => NormalBalance::DEBIT,
            AccountTypeEnum::LIABILITY, AccountTypeEnum::EQUITY, AccountTypeEnum::INCOME => NormalBalance::CREDIT,
        };
    }

    /**
     * Get all presets by type
     */
    public static function byType(AccountTypeEnum $type): array
    {
        return array_filter(
            self::cases(),
            static fn($preset) => $preset->type() === $type
        );
    }

    /**
     * Get presets for a specific category
     */
    public static function byCategory(string $category): array
    {
        $categories = [
            'current_assets' => [
                self::CASH, self::ACCOUNTS_RECEIVABLE, self::INVENTORY,
                self::PREPAID_EXPENSES, self::MARKETABLE_SECURITIES,
                self::SUPPLIES,
            ],
            'fixed_assets' => [
                self::LAND, self::BUILDINGS, self::MACHINERY_AND_EQUIPMENT,
                self::VEHICLES, self::ACCUMULATED_DEPRECIATION,
                self::LEASED_ASSET,
            ],
            'current_liabilities' => [
                self::ACCOUNTS_PAYABLE, self::SALES_TAX_PAYABLE,
                self::WAGES_PAYABLE, self::UNEARNED_REVENUE,
            ],
            'operating_revenue' => [
                self::SALES_REVENUE, self::SERVICE_REVENUE,
                self::RENTAL_REVENUE,
            ],
            'operating_expenses' => [
                self::SALARIES_EXPENSE, self::RENT_EXPENSE,
                self::UTILITIES_EXPENSE, self::ADVERTISING_EXPENSE,
            ],
            'tax_accounts' => [
                self::SALES_TAX_PAYABLE, self::VAT_PAYABLE,
                self::INPUT_VAT, self::OUTPUT_VAT,
                self::INCOME_TAX_PAYABLE,
            ],
            'equity' => [
                self::COMMON_STOCK, self::ADDITIONAL_PAID_IN_CAPITAL, self::RETAINED_EARNINGS,
                self::PREFERRED_STOCK, self::CURRENT_YEAR_EARNINGS, self::OWNERS_EQUITY,
                self::DRAWINGS, self::DIVIDENDS_DECLARED,
                self::INCOME_SUMMARY, self::DONATED_CAPITAL,
            ],
            'inventory' => [
                self::RAW_MATERIALS, self::WORK_IN_PROGRESS, self::FINISHED_GOODS,
                self::MERCHANDISE_INVENTORY
            ],
            'payroll_liability' => [
                self::WAGES_PAYABLE, self::SALARIES_PAYABLE, self::BONUSES_PAYABLE,
                self::COMMISSIONS_PAYABLE,
            ],
            'gain_loss' => [
                self::GAIN_ON_SALE_OF_ASSETS, self::LOSS_ON_SALE_OF_ASSETS,
                self::UNREALIZED_GAIN, self::UNREALIZED_LOSS,
                self::IMPAIRMENT_LOSS,
            ],
            'intangible_assets' => [
                self::INTANGIBLE_ASSETS, self::GOODWILL, self::PATENTS,
                self::COPYRIGHTS, self::TRADEMARKS, self::FRANCHISE_RIGHTS,
                self::SOFTWARE, self::ACCUMULATED_AMORTIZATION,
            ],
            'bond_accounts' => [
                self::BONDS_PAYABLE, self::DISCOUNT_ON_BONDS,
                self::PREMIUM_ON_BONDS, self::BOND_ISSUANCE_COSTS,
            ]
        ];

        return $categories[$category] ?? [];
    }

    /**
     * These presets should never be available for account creation.
     * That means we should not allow users to create accounts with these presets.
     * @return bool
     */
    public function isParent(): bool
    {
        return in_array($this, [
            self::FIXED_ASSETS,
            self::EXPENSES,
            self::PAYROLL_EXPENSES,
            self::OTHER_EXPENSES,
            self::FINANCIAL_EXPENSES,
            self::TRAVEL_EXPENSE,
            self::PROFESSIONAL_FEES,
            self::MARKETING_SALES_EXPENSES,
            self::OFFICE_EXPENSES,
            self::OCCUPANCY_EXPENSES,
            self::PAYROLL_EXPENSES,
            self::OPERATING_EXPENSES,
            self::COST_OF_GOODS_SOLD,
            self::ACCRUED_EXPENSES,
            self::ACCOUNTS_PAYABLE,
            self::ACCUMULATED_DEPRECIATION,
            self::FIXED_ASSETS,
            self::PREPAID_EXPENSES,
            self::INVENTORY,
            self::INVENTORY,
            self::CASH_AND_CASH_EQUIVALENT
        ]);
    }

    public static function byIndustry()
    {
        return [
            "full" => self::cases(),
            "retail" => collect(self::cases())->except([

            ]),
            "saas" => collect(self::cases())->except([

            ]),
            "manufacturing" => collect(self::cases())->except([

            ])
        ];
    }
}
