<?php

namespace RingleSoft\LaravelAccounting\Enums;

use RingleSoft\LaravelAccounting\Attributes\PresetAccount;
use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Support\PresetDefinitions;

/**
 * Standard Chart of Accounts Presets
 * Based on IFRS/US GAAP with common industry variations
 */
enum AccountPreset: string implements AccountPresetContract
{
    // ====================================
    // CHART STRUCTURE
    // ====================================

    // Root (first-level) accounts: the fixed frame of the chart. They can't
    // be changed or deleted, and no other root accounts can be created (the
    // other roots are COST_OF_GOODS_SOLD, OPERATING_EXPENSES,
    // FINANCIAL_EXPENSES, INCOME_TAX_EXPENSE and OTHER_EXPENSES).
    #[PresetAccount(name: 'Current Assets', type: AccountTypeEnum::ASSET, code: '10000', category: 'Current Assets', reportSection: 'Assets', allowsManualEntries: false, isSystemAccount: true)]
    case CURRENT_ASSETS = 'CURRENT_ASSETS';

    #[PresetAccount(name: 'Non-Current Assets', type: AccountTypeEnum::ASSET, code: '15000', category: 'Non-Current Assets', reportSection: 'Assets', allowsManualEntries: false, isSystemAccount: true)]
    case NON_CURRENT_ASSETS = 'NON_CURRENT_ASSETS';

    #[PresetAccount(name: 'Current Liabilities', type: AccountTypeEnum::LIABILITY, code: '20000', category: 'Current Liabilities', reportSection: 'Liabilities', allowsManualEntries: false, isSystemAccount: true)]
    case CURRENT_LIABILITIES = 'CURRENT_LIABILITIES';

    #[PresetAccount(name: 'Long-term Liabilities', type: AccountTypeEnum::LIABILITY, code: '25000', category: 'Long-term Liabilities', reportSection: 'Liabilities', allowsManualEntries: false, isSystemAccount: true)]
    case LONG_TERM_LIABILITIES = 'LONG_TERM_LIABILITIES';

    #[PresetAccount(name: 'Equity', type: AccountTypeEnum::EQUITY, code: '30000', category: 'Equity', reportSection: 'Equity', allowsManualEntries: false, isSystemAccount: true)]
    case EQUITY = 'EQUITY';

    #[PresetAccount(name: 'Revenue', type: AccountTypeEnum::INCOME, code: '40000', category: 'Revenue', reportSection: 'Income', allowsManualEntries: false, isSystemAccount: true)]
    case REVENUE = 'REVENUE';

    // Second-level groups
    #[PresetAccount(name: 'Tax Liabilities', type: AccountTypeEnum::LIABILITY, parent: self::CURRENT_LIABILITIES, code: '20500', allowsManualEntries: false)]
    case TAX_LIABILITIES = 'TAX_LIABILITIES';

    #[PresetAccount(name: 'Payroll Liabilities', type: AccountTypeEnum::LIABILITY, parent: self::CURRENT_LIABILITIES, code: '20600', allowsManualEntries: false)]
    case PAYROLL_LIABILITIES = 'PAYROLL_LIABILITIES';

    #[PresetAccount(name: 'Operating Revenue', type: AccountTypeEnum::INCOME, parent: self::REVENUE, code: '40100', allowsManualEntries: false)]
    case OPERATING_REVENUE = 'OPERATING_REVENUE';

    #[PresetAccount(name: 'Other Revenue', type: AccountTypeEnum::INCOME, parent: self::REVENUE, code: '40200', allowsManualEntries: false)]
    case OTHER_REVENUE = 'OTHER_REVENUE';

    #[PresetAccount(name: 'Sales Returns and Allowances', type: AccountTypeEnum::INCOME, parent: self::REVENUE, code: '40900', normalBalance: NormalBalance::DEBIT, subCategory: 'Contra Revenue', allowsManualEntries: false)]
    case SALES_RETURNS_AND_ALLOWANCES = 'SALES_RETURNS_AND_ALLOWANCES';

    // ====================================
    // ASSETS (1xxx - 2xxx series)
    // ====================================

    // Current Assets (1000-1499)
    #[PresetAccount(name: 'Cash', type: AccountTypeEnum::ASSET, parent: self::CASH_AND_CASH_EQUIVALENT, code: '10101')]
    case CASH = 'CASH';
    #[PresetAccount(name: 'Petty Cash', type: AccountTypeEnum::ASSET, parent: self::CASH_AND_CASH_EQUIVALENT, code: '10102')]
    case PETTY_CASH = 'PETTY_CASH';
    case CASH_ON_HAND = 'CASH_ON_HAND';
    #[PresetAccount(name: 'Cash in Bank', type: AccountTypeEnum::ASSET, parent: self::CASH_AND_CASH_EQUIVALENT, code: '10103')]
    case CASH_IN_BANK = 'CASH_IN_BANK';
    #[PresetAccount(name: 'Cash and Cash Equivalents', type: AccountTypeEnum::ASSET, parent: self::CURRENT_ASSETS, code: '10100', allowsManualEntries: false)]
    case CASH_AND_CASH_EQUIVALENT = 'CASH_AND_CASH_EQUIVALENT';
    #[PresetAccount(name: 'Undeposited Funds', type: AccountTypeEnum::ASSET, parent: self::CASH_AND_CASH_EQUIVALENT, code: '10104')]
    case UNDEPOSITED_FUNDS = 'UNDEPOSITED_FUNDS';

    #[PresetAccount(name: 'Accounts Receivable', type: AccountTypeEnum::ASSET, parent: self::CURRENT_ASSETS, code: '10200')]

    case ACCOUNTS_RECEIVABLE = 'ACCOUNTS_RECEIVABLE';
    #[PresetAccount(name: 'Trade Receivables', type: AccountTypeEnum::ASSET, parent: self::ACCOUNTS_RECEIVABLE, code: '10201')]
    case TRADE_RECEIVABLES = 'TRADE_RECEIVABLES';
    #[PresetAccount(name: 'Notes Receivable', type: AccountTypeEnum::ASSET, parent: self::ACCOUNTS_RECEIVABLE, code: '10202')]
    case NOTES_RECEIVABLE = 'NOTES_RECEIVABLE';
    #[PresetAccount(name: 'Other Receivables', type: AccountTypeEnum::ASSET, parent: self::ACCOUNTS_RECEIVABLE, code: '10203')]
    case OTHER_RECEIVABLES = 'OTHER_RECEIVABLES';
    case UNBILLED_REVENUE = 'UNBILLED_REVENUE';
    #[PresetAccount(name: 'Accrued Revenue', type: AccountTypeEnum::ASSET, parent: self::ACCOUNTS_RECEIVABLE, code: '10204')]
    case ACCRUED_REVENUE = 'ACCRUED_REVENUE';

    #[PresetAccount(name: 'Allowance for Doubtful Accounts', type: AccountTypeEnum::ASSET, parent: self::ACCOUNTS_RECEIVABLE, code: '10210', normalBalance: NormalBalance::CREDIT, subCategory: 'Contra Asset')]

    case ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS = 'ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS';
    case ALLOWANCE_FOR_BAD_DEBTS = 'ALLOWANCE_FOR_BAD_DEBTS';

    #[PresetAccount(name: 'Inventory', type: AccountTypeEnum::ASSET, parent: self::CURRENT_ASSETS, code: '10300', allowsManualEntries: false)]

    case INVENTORY = 'INVENTORY';
    #[PresetAccount(name: 'Raw Materials', type: AccountTypeEnum::ASSET, parent: self::INVENTORY, code: '10301')]
    case RAW_MATERIALS = 'RAW_MATERIALS';
    #[PresetAccount(name: 'Work in Progress', type: AccountTypeEnum::ASSET, parent: self::INVENTORY, code: '10302')]
    case WORK_IN_PROGRESS = 'WORK_IN_PROGRESS';
    #[PresetAccount(name: 'Finished Goods', type: AccountTypeEnum::ASSET, parent: self::INVENTORY, code: '10303')]
    case FINISHED_GOODS = 'FINISHED_GOODS';
    #[PresetAccount(name: 'Merchandise Inventory', type: AccountTypeEnum::ASSET, parent: self::INVENTORY, code: '10304')]
    case MERCHANDISE_INVENTORY = 'MERCHANDISE_INVENTORY';

    #[PresetAccount(name: 'Prepaid Expenses', type: AccountTypeEnum::ASSET, parent: self::CURRENT_ASSETS, code: '10400')]

    case PREPAID_EXPENSES = 'PREPAID_EXPENSES';
    #[PresetAccount(name: 'Prepaid Insurance', type: AccountTypeEnum::ASSET, parent: self::PREPAID_EXPENSES, code: '10401')]
    case PREPAID_INSURANCE = 'PREPAID_INSURANCE';
    #[PresetAccount(name: 'Prepaid Rent', type: AccountTypeEnum::ASSET, parent: self::PREPAID_EXPENSES, code: '10402')]
    case PREPAID_RENT = 'PREPAID_RENT';
    #[PresetAccount(name: 'Prepaid Subscriptions', type: AccountTypeEnum::ASSET, parent: self::PREPAID_EXPENSES, code: '10403')]
    case PREPAID_SUBSCRIPTIONS = 'PREPAID_SUBSCRIPTIONS';

    case SUPPLIES = 'SUPPLIES';

    case PREPAID_TAXES = 'PREPAID_TAXES';
    case PREPAID_INCOME_TAX = 'PREPAID_INCOME_TAX';
    #[PresetAccount(name: 'Advance to Suppliers', type: AccountTypeEnum::ASSET, parent: self::PREPAID_EXPENSES, code: '10405')]
    case ADVANCE_TO_SUPPLIERS = 'ADVANCE_TO_SUPPLIERS';
    #[PresetAccount(name: 'Advance to Employees', type: AccountTypeEnum::ASSET, parent: self::PREPAID_EXPENSES, code: '10406')]
    case ADVANCE_TO_EMPLOYEES = 'ADVANCE_TO_EMPLOYEES';

    // Short-term Investments (1500-1599)
    case MARKETABLE_SECURITIES = 'MARKETABLE_SECURITIES';
    #[PresetAccount(name: 'Short-term Investments', type: AccountTypeEnum::ASSET, parent: self::CURRENT_ASSETS, code: '10600')]
    case SHORT_TERM_INVESTMENTS = 'SHORT_TERM_INVESTMENTS';
    case TRADING_SECURITIES = 'TRADING_SECURITIES';
    case FAIR_VALUE_ADJUSTMENT = 'FAIR_VALUE_ADJUSTMENT';
    #[PresetAccount(name: 'Digital Assets', type: AccountTypeEnum::ASSET, parent: self::CURRENT_ASSETS, code: '10700')]
    case DIGITAL_ASSETS = 'DIGITAL_ASSETS';

    // Non-Current Assets (1600-1999)
    #[PresetAccount(name: 'Long-term Investments', type: AccountTypeEnum::ASSET, parent: self::NON_CURRENT_ASSETS, code: '15400')]
    case LONG_TERM_INVESTMENTS = 'LONG_TERM_INVESTMENTS';
    case HELD_TO_MATURITY_SECURITIES = 'HELD_TO_MATURITY_SECURITIES';
    case AVAILABLE_FOR_SALE_SECURITIES = 'AVAILABLE_FOR_SALE_SECURITIES';

    // Property, Plant & Equipment (2000-2499)
    #[PresetAccount(name: 'Land', type: AccountTypeEnum::ASSET, parent: self::FIXED_ASSETS, code: '15101')]
    case LAND = 'LAND';
    #[PresetAccount(name: 'Buildings', type: AccountTypeEnum::ASSET, parent: self::FIXED_ASSETS, code: '15102')]
    case BUILDINGS = 'BUILDINGS';
    case BUILDING_IMPROVEMENTS = 'BUILDING_IMPROVEMENTS';
    #[PresetAccount(name: 'Leasehold Improvements', type: AccountTypeEnum::ASSET, parent: self::FIXED_ASSETS, code: '15108')]
    case LEASEHOLD_IMPROVEMENTS = 'LEASEHOLD_IMPROVEMENTS';
    #[PresetAccount(name: 'Construction in Progress', type: AccountTypeEnum::ASSET, parent: self::FIXED_ASSETS, code: '15109')]
    case CONSTRUCTION_IN_PROGRESS = 'CONSTRUCTION_IN_PROGRESS';

    #[PresetAccount(name: 'Machinery and Equipment', type: AccountTypeEnum::ASSET, parent: self::FIXED_ASSETS, code: '15103')]

    case MACHINERY_AND_EQUIPMENT = 'MACHINERY_AND_EQUIPMENT';
    #[PresetAccount(name: 'Office Equipment', type: AccountTypeEnum::ASSET, parent: self::FIXED_ASSETS, code: '15104')]
    case OFFICE_EQUIPMENT = 'OFFICE_EQUIPMENT';
    #[PresetAccount(name: 'IT Equipment', type: AccountTypeEnum::ASSET, parent: self::FIXED_ASSETS, code: '15107')]
    case COMPUTER_EQUIPMENT = 'COMPUTER_EQUIPMENT';
    #[PresetAccount(name: 'Furniture and Fixtures', type: AccountTypeEnum::ASSET, parent: self::FIXED_ASSETS, code: '15105')]
    case FURNITURE_AND_FIXTURES = 'FURNITURE_AND_FIXTURES';
    #[PresetAccount(name: 'Vehicles', type: AccountTypeEnum::ASSET, parent: self::FIXED_ASSETS, code: '15106')]
    case VEHICLES = 'VEHICLES';
    case LEASED_ASSET = 'LEASED_ASSET';

    // Accumulated Depreciation (Contra-Asset) (2500-2799)
    #[PresetAccount(name: 'Accumulated Depreciation', type: AccountTypeEnum::ASSET, parent: self::NON_CURRENT_ASSETS, code: '15200', normalBalance: NormalBalance::CREDIT, subCategory: 'Contra Asset')]
    case ACCUMULATED_DEPRECIATION = 'ACCUMULATED_DEPRECIATION';
    #[PresetAccount(name: 'Accumulated Depreciation - Buildings', type: AccountTypeEnum::ASSET, parent: self::ACCUMULATED_DEPRECIATION, code: '15201', normalBalance: NormalBalance::CREDIT, subCategory: 'Contra Asset')]
    case ACCUM_DEPR_BUILDINGS = 'ACCUM_DEPR_BUILDINGS';
    #[PresetAccount(name: 'Accumulated Depreciation - Equipment', type: AccountTypeEnum::ASSET, parent: self::ACCUMULATED_DEPRECIATION, code: '15202', normalBalance: NormalBalance::CREDIT, subCategory: 'Contra Asset')]
    case ACCUM_DEPR_EQUIPMENT = 'ACCUM_DEPR_EQUIPMENT';
    #[PresetAccount(name: 'Accumulated Depreciation - Vehicles', type: AccountTypeEnum::ASSET, parent: self::ACCUMULATED_DEPRECIATION, code: '15203', normalBalance: NormalBalance::CREDIT, subCategory: 'Contra Asset')]
    case ACCUM_DEPR_VEHICLES = 'ACCUM_DEPR_VEHICLES';
    #[PresetAccount(name: 'Accumulated Depreciation - Furniture', type: AccountTypeEnum::ASSET, parent: self::ACCUMULATED_DEPRECIATION, code: '15204', normalBalance: NormalBalance::CREDIT, subCategory: 'Contra Asset')]
    case ACCUM_DEPR_FURNITURE = 'ACCUM_DEPR_FURNITURE';
    #[PresetAccount(name: 'Accumulated Depreciation - Office Equipment', type: AccountTypeEnum::ASSET, parent: self::ACCUMULATED_DEPRECIATION, code: '15205', normalBalance: NormalBalance::CREDIT, subCategory: 'Contra Asset')]
    case ACCUM_DEPR_OFFICE_EQUIPMENT = 'ACCUM_DEPR_OFFICE_EQUIPMENT';
    #[PresetAccount(name: 'Accumulated Depreciation - IT Equipment', type: AccountTypeEnum::ASSET, parent: self::ACCUMULATED_DEPRECIATION, code: '15206', normalBalance: NormalBalance::CREDIT, subCategory: 'Contra Asset')]
    case ACCUM_DEPR_IT_EQUIPMENT = 'ACCUM_DEPR_IT_EQUIPMENT';
    #[PresetAccount(name: 'Accumulated Depreciation - Leasehold Improvements', type: AccountTypeEnum::ASSET, parent: self::ACCUMULATED_DEPRECIATION, code: '15207', normalBalance: NormalBalance::CREDIT, subCategory: 'Contra Asset')]
    case ACCUM_DEPR_LEASEHOLD = 'ACCUM_DEPR_LEASEHOLD';
    case ACCUMULATED_IMPAIRMENT = 'ACCUMULATED_IMPAIRMENT';

    // Intangible Assets (2800-2899)
    #[PresetAccount(name: 'Intangible Assets', type: AccountTypeEnum::ASSET, parent: self::NON_CURRENT_ASSETS, code: '15300', allowsManualEntries: false)]
    case INTANGIBLE_ASSETS = 'INTANGIBLE_ASSETS';
    #[PresetAccount(name: 'Goodwill', type: AccountTypeEnum::ASSET, parent: self::INTANGIBLE_ASSETS, code: '15301')]
    case GOODWILL = 'GOODWILL';
    #[PresetAccount(name: 'Patents', type: AccountTypeEnum::ASSET, parent: self::INTANGIBLE_ASSETS, code: '15302')]
    case PATENTS = 'PATENTS';
    #[PresetAccount(name: 'Copyrights', type: AccountTypeEnum::ASSET, parent: self::INTANGIBLE_ASSETS, code: '15305')]
    case COPYRIGHTS = 'COPYRIGHTS';
    #[PresetAccount(name: 'Trademarks', type: AccountTypeEnum::ASSET, parent: self::INTANGIBLE_ASSETS, code: '15304')]
    case TRADEMARKS = 'TRADEMARKS';
    case FRANCHISE_RIGHTS = 'FRANCHISE_RIGHTS';
    #[PresetAccount(name: 'Software', type: AccountTypeEnum::ASSET, parent: self::INTANGIBLE_ASSETS, code: '15303')]
    case SOFTWARE = 'SOFTWARE';

    #[PresetAccount(name: 'Accumulated Amortization', type: AccountTypeEnum::ASSET, parent: self::INTANGIBLE_ASSETS, code: '15310', normalBalance: NormalBalance::CREDIT, subCategory: 'Contra Asset')]

    case ACCUMULATED_AMORTIZATION = 'ACCUMULATED_AMORTIZATION';
    #[PresetAccount(name: 'Accumulated Amortization - Patents', type: AccountTypeEnum::ASSET, parent: self::ACCUMULATED_AMORTIZATION, code: '15311', normalBalance: NormalBalance::CREDIT, subCategory: 'Contra Asset')]
    case ACCUM_AMORT_PATENTS = 'ACCUM_AMORT_PATENTS';
    #[PresetAccount(name: 'Accumulated Amortization - Software', type: AccountTypeEnum::ASSET, parent: self::ACCUMULATED_AMORTIZATION, code: '15312', normalBalance: NormalBalance::CREDIT, subCategory: 'Contra Asset')]
    case ACCUM_AMORT_SOFTWARE = 'ACCUM_AMORT_SOFTWARE';
    #[PresetAccount(name: 'Accumulated Amortization - Trademarks', type: AccountTypeEnum::ASSET, parent: self::ACCUMULATED_AMORTIZATION, code: '15313', normalBalance: NormalBalance::CREDIT, subCategory: 'Contra Asset')]
    case ACCUM_AMORT_TRADEMARKS = 'ACCUM_AMORT_TRADEMARKS';
    #[PresetAccount(name: 'Accumulated Amortization - Copyrights', type: AccountTypeEnum::ASSET, parent: self::ACCUMULATED_AMORTIZATION, code: '15314', normalBalance: NormalBalance::CREDIT, subCategory: 'Contra Asset')]
    case ACCUM_AMORT_COPYRIGHTS = 'ACCUM_AMORT_COPYRIGHTS';

    // Other Assets (2900-2999)
    #[PresetAccount(name: 'Deferred Tax Asset', type: AccountTypeEnum::ASSET, parent: self::NON_CURRENT_ASSETS, code: '15600')]
    case DEFERRED_TAX_ASSETS = 'DEFERRED_TAX_ASSETS';
    #[PresetAccount(name: 'Right-of-Use Assets', type: AccountTypeEnum::ASSET, parent: self::NON_CURRENT_ASSETS, code: '15500')]
    case RIGHT_OF_USE_ASSETS = 'RIGHT_OF_USE_ASSETS';
    case BOND_ISSUANCE_COSTS = 'BOND_ISSUANCE_COSTS';
    case DEPOSITS = 'DEPOSITS';
    #[PresetAccount(name: 'Security Deposits', type: AccountTypeEnum::ASSET, parent: self::NON_CURRENT_ASSETS, code: '15450')]
    case SECURITY_DEPOSITS = 'SECURITY_DEPOSITS';

    // ====================================
    // LIABILITIES (3xxx - 4xxx series)
    // ====================================

    // Current Liabilities (3000-3499)
    #[PresetAccount(name: 'Accounts Payable', type: AccountTypeEnum::LIABILITY, parent: self::CURRENT_LIABILITIES, code: '20100')]
    case ACCOUNTS_PAYABLE = 'ACCOUNTS_PAYABLE';
    #[PresetAccount(name: 'Trade Payables', type: AccountTypeEnum::LIABILITY, parent: self::ACCOUNTS_PAYABLE, code: '20101')]
    case TRADE_PAYABLES = 'TRADE_PAYABLES';
    #[PresetAccount(name: 'Notes Payable', type: AccountTypeEnum::LIABILITY, parent: self::ACCOUNTS_PAYABLE, code: '20102')]
    case NOTES_PAYABLE = 'NOTES_PAYABLE';
    case OTHER_PAYABLES = 'OTHER_PAYABLES';

    #[PresetAccount(name: 'Accrued Expenses', type: AccountTypeEnum::LIABILITY, parent: self::CURRENT_LIABILITIES, code: '20200')]

    case ACCRUED_EXPENSES = 'ACCRUED_EXPENSES';
    #[PresetAccount(name: 'Accrued Salaries', type: AccountTypeEnum::LIABILITY, parent: self::ACCRUED_EXPENSES, code: '20201')]
    case ACCRUED_SALARIES = 'ACCRUED_SALARIES';
    #[PresetAccount(name: 'Accrued Interest', type: AccountTypeEnum::LIABILITY, parent: self::ACCRUED_EXPENSES, code: '20202')]
    case ACCRUED_INTEREST = 'ACCRUED_INTEREST';
    #[PresetAccount(name: 'Accrued Utilities', type: AccountTypeEnum::LIABILITY, parent: self::ACCRUED_EXPENSES, code: '20204')]
    case ACCRUED_UTILITIES = 'ACCRUED_UTILITIES';
    case ACCRUED_TAXES = 'ACCRUED_TAXES';
    #[PresetAccount(name: 'Dividends Payable', type: AccountTypeEnum::LIABILITY, parent: self::CURRENT_LIABILITIES, code: '20105')]
    case DIVIDENDS_PAYABLE = 'DIVIDENDS_PAYABLE';
    #[PresetAccount(name: 'Lease Liability (Current)', type: AccountTypeEnum::LIABILITY, parent: self::ACCRUED_EXPENSES, code: '20203')]
    case LEASE_LIABILITY_CURRENT = 'LEASE_LIABILITY_CURRENT';

    #[PresetAccount(name: 'Short-term Loans', type: AccountTypeEnum::LIABILITY, parent: self::CURRENT_LIABILITIES, code: '20300')]

    case SHORT_TERM_LOANS = 'SHORT_TERM_LOANS';
    #[PresetAccount(name: 'Current Portion of Long-term Debt', type: AccountTypeEnum::LIABILITY, parent: self::CURRENT_LIABILITIES, code: '20301')]
    case CURRENT_PORTION_OF_LONG_TERM_DEBT = 'CURRENT_PORTION_OF_LONG_TERM_DEBT';
    #[PresetAccount(name: 'Line of Credit', type: AccountTypeEnum::LIABILITY, parent: self::CURRENT_LIABILITIES, code: '20302')]
    case LINE_OF_CREDIT = 'LINE_OF_CREDIT';
    case BANK_OVERDRAFT = 'BANK_OVERDRAFT';

    #[PresetAccount(name: 'Unearned Revenue', type: AccountTypeEnum::LIABILITY, parent: self::CURRENT_LIABILITIES, code: '20400')]

    case UNEARNED_REVENUE = 'UNEARNED_REVENUE';
    #[PresetAccount(name: 'Deferred Revenue', type: AccountTypeEnum::LIABILITY, parent: self::CURRENT_LIABILITIES, code: '20402')]
    case DEFERRED_REVENUE = 'DEFERRED_REVENUE';
    #[PresetAccount(name: 'Customer Deposits', type: AccountTypeEnum::LIABILITY, parent: self::CURRENT_LIABILITIES, code: '20401')]
    case CUSTOMER_DEPOSITS = 'CUSTOMER_DEPOSITS';
    #[PresetAccount(name: 'Advances from Customers', type: AccountTypeEnum::LIABILITY, parent: self::CURRENT_LIABILITIES, code: '20403')]
    case ADVANCES_FROM_CUSTOMERS = 'ADVANCES_FROM_CUSTOMERS';

    // Tax Liabilities (3500-3599)
    #[PresetAccount(name: 'Sales Tax Payable', type: AccountTypeEnum::LIABILITY, parent: self::TAX_LIABILITIES, code: '20501')]
    case SALES_TAX_PAYABLE = 'SALES_TAX_PAYABLE';
    #[PresetAccount(name: 'VAT Payable', type: AccountTypeEnum::LIABILITY, parent: self::TAX_LIABILITIES, code: '20502')]
    case VAT_PAYABLE = 'VAT_PAYABLE';
    #[PresetAccount(name: 'GST Payable', type: AccountTypeEnum::LIABILITY, parent: self::TAX_LIABILITIES, code: '20504')]
    case GST_PAYABLE = 'GST_PAYABLE';
    #[PresetAccount(name: 'Income Tax Payable', type: AccountTypeEnum::LIABILITY, parent: self::TAX_LIABILITIES, code: '20503')]
    case INCOME_TAX_PAYABLE = 'INCOME_TAX_PAYABLE';
    #[PresetAccount(name: 'Payroll Tax Payable', type: AccountTypeEnum::LIABILITY, parent: self::TAX_LIABILITIES, code: '20505')]
    case PAYROLL_TAX_PAYABLE = 'PAYROLL_TAX_PAYABLE';

    // Payroll Liabilities (3600-3699)
    #[PresetAccount(name: 'Wages Payable', type: AccountTypeEnum::LIABILITY, parent: self::PAYROLL_LIABILITIES, code: '20601')]
    case WAGES_PAYABLE = 'WAGES_PAYABLE';
    #[PresetAccount(name: 'Salaries Payable', type: AccountTypeEnum::LIABILITY, parent: self::PAYROLL_LIABILITIES, code: '20602')]
    case SALARIES_PAYABLE = 'SALARIES_PAYABLE';
    #[PresetAccount(name: 'Bonuses Payable', type: AccountTypeEnum::LIABILITY, parent: self::PAYROLL_LIABILITIES, code: '20603')]
    case BONUSES_PAYABLE = 'BONUSES_PAYABLE';
    case COMMISSIONS_PAYABLE = 'COMMISSIONS_PAYABLE';
    #[PresetAccount(name: 'Payroll Clearing', type: AccountTypeEnum::LIABILITY, parent: self::PAYROLL_LIABILITIES, code: '20610')]
    case PAYROLL_CLEARING = 'PAYROLL_CLEARING';
    #[PresetAccount(name: 'Federal Withholding Payable', type: AccountTypeEnum::LIABILITY, parent: self::PAYROLL_LIABILITIES, code: '20611')]
    case FEDERAL_WITHHOLDING_PAYABLE = 'FEDERAL_WITHHOLDING_PAYABLE';
    case STATE_WITHHOLDING_PAYABLE = 'STATE_WITHHOLDING_PAYABLE';
    #[PresetAccount(name: 'Social Security Payable', type: AccountTypeEnum::LIABILITY, parent: self::PAYROLL_LIABILITIES, code: '20612')]
    case SOCIAL_SECURITY_PAYABLE = 'SOCIAL_SECURITY_PAYABLE';
    #[PresetAccount(name: 'Medicare Payable', type: AccountTypeEnum::LIABILITY, parent: self::PAYROLL_LIABILITIES, code: '20613')]
    case MEDICARE_PAYABLE = 'MEDICARE_PAYABLE';
    #[PresetAccount(name: 'Vacation Payable', type: AccountTypeEnum::LIABILITY, parent: self::PAYROLL_LIABILITIES, code: '20606')]
    case VACATION_PAYABLE = 'VACATION_PAYABLE';

    case EMPLOYEE_BENEFITS_PAYABLE = 'EMPLOYEE_BENEFITS_PAYABLE';
    case EMPLOYEE_INSURANCE_PAYABLE = 'EMPLOYEE_INSURANCE_PAYABLE';
    case PENSION_LIABILITIES = 'PENSION_LIABILITIES';
    case EMPLOYEE_PROVIDENT_FUND = 'EMPLOYEE_PROVIDENT_FUND';

    // Long-term Liabilities (3700-3999)
    #[PresetAccount(name: 'Long-term Debt', type: AccountTypeEnum::LIABILITY, parent: self::LONG_TERM_LIABILITIES, code: '25001')]
    case LONG_TERM_DEBT = 'LONG_TERM_DEBT';
    #[PresetAccount(name: 'Bonds Payable', type: AccountTypeEnum::LIABILITY, parent: self::LONG_TERM_LIABILITIES, code: '25002')]
    case BONDS_PAYABLE = 'BONDS_PAYABLE';
    case DISCOUNT_ON_BONDS = 'DISCOUNT_ON_BONDS';
    case PREMIUM_ON_BONDS = 'PREMIUM_ON_BONDS';
    #[PresetAccount(name: 'Mortgage Payable', type: AccountTypeEnum::LIABILITY, parent: self::LONG_TERM_LIABILITIES, code: '25003')]
    case MORTGAGE_PAYABLE = 'MORTGAGE_PAYABLE';
    #[PresetAccount(name: 'Lease Liability (Non-Current)', type: AccountTypeEnum::LIABILITY, parent: self::LONG_TERM_LIABILITIES, code: '25005')]
    case LEASE_LIABILITIES = 'LEASE_LIABILITIES';

    #[PresetAccount(name: 'Deferred Tax Liability', type: AccountTypeEnum::LIABILITY, parent: self::LONG_TERM_LIABILITIES, code: '25004')]

    case DEFERRED_TAX_LIABILITIES = 'DEFERRED_TAX_LIABILITIES';
    case WARRANTY_LIABILITIES = 'WARRANTY_LIABILITIES';

    // ====================================
    // EQUITY (5xxx series)
    // ====================================

    #[PresetAccount(name: 'Common Stock', type: AccountTypeEnum::EQUITY, parent: self::EQUITY, code: '30100')]

    case COMMON_STOCK = 'COMMON_STOCK';
    #[PresetAccount(name: 'Preferred Stock', type: AccountTypeEnum::EQUITY, parent: self::EQUITY, code: '30300')]
    case PREFERRED_STOCK = 'PREFERRED_STOCK';
    #[PresetAccount(name: 'Additional Paid-in Capital', type: AccountTypeEnum::EQUITY, parent: self::EQUITY, code: '30200')]
    case ADDITIONAL_PAID_IN_CAPITAL = 'ADDITIONAL_PAID_IN_CAPITAL';
    #[PresetAccount(name: 'Treasury Stock', type: AccountTypeEnum::EQUITY, parent: self::EQUITY, code: '30700', normalBalance: NormalBalance::DEBIT, subCategory: 'Contra Equity')]
    case TREASURY_STOCK = 'TREASURY_STOCK';

    #[PresetAccount(name: 'Retained Earnings', type: AccountTypeEnum::EQUITY, parent: self::EQUITY, code: '30400', isSystemAccount: true)]

    case RETAINED_EARNINGS = 'RETAINED_EARNINGS';
    #[PresetAccount(name: 'Current Year Earnings', type: AccountTypeEnum::EQUITY, parent: self::EQUITY, code: '30410', isSystemAccount: true)]
    case CURRENT_YEAR_EARNINGS = 'CURRENT_YEAR_EARNINGS';
    case PRIOR_YEAR_RETAINED_EARNINGS = 'PRIOR_YEAR_RETAINED_EARNINGS';

    #[PresetAccount(name: 'Owner\'s Equity', type: AccountTypeEnum::EQUITY, parent: self::EQUITY, code: '30500')]

    case OWNERS_EQUITY = 'OWNERS_EQUITY';
    case PARTNERS_EQUITY = 'PARTNERS_EQUITY';
    case MEMBERS_EQUITY = 'MEMBERS_EQUITY';

    case DRAWINGS = 'DRAWINGS';
    #[PresetAccount(name: 'Owner\'s Draw', type: AccountTypeEnum::EQUITY, parent: self::EQUITY, code: '30510', normalBalance: NormalBalance::DEBIT, subCategory: 'Contra Equity')]
    case OWNERS_DRAW = 'OWNERS_DRAW';
    #[PresetAccount(name: 'Dividends Declared', type: AccountTypeEnum::EQUITY, parent: self::EQUITY, code: '30600', normalBalance: NormalBalance::DEBIT, subCategory: 'Contra Equity')]
    case DIVIDENDS_DECLARED = 'DIVIDENDS_DECLARED';

    case INCOME_SUMMARY = 'INCOME_SUMMARY';
    case DONATED_CAPITAL = 'DONATED_CAPITAL';
    #[PresetAccount(name: 'Opening Balance Equity', type: AccountTypeEnum::EQUITY, parent: self::EQUITY, code: '30050', isSystemAccount: true)]
    case OPENING_BALANCE_EQUITY = 'OPENING_BALANCE_EQUITY';

    #[PresetAccount(name: 'Accumulated Other Comprehensive Income', type: AccountTypeEnum::EQUITY, parent: self::EQUITY, code: '30800')]

    case OTHER_COMPREHENSIVE_INCOME = 'OTHER_COMPREHENSIVE_INCOME';
    case REVALUATION_RESERVE = 'REVALUATION_RESERVE';
    case FOREIGN_CURRENCY_TRANSLATION = 'FOREIGN_CURRENCY_TRANSLATION';

    // ====================================
    // REVENUE (6xxx series)
    // ====================================

    // Operating Revenue (6000-6499)
    #[PresetAccount(name: 'Sales Revenue', type: AccountTypeEnum::INCOME, parent: self::OPERATING_REVENUE, code: '40101')]
    case SALES_REVENUE = 'SALES_REVENUE';
    #[PresetAccount(name: 'Service Revenue', type: AccountTypeEnum::INCOME, parent: self::OPERATING_REVENUE, code: '40102')]
    case SERVICE_REVENUE = 'SERVICE_REVENUE';
    #[PresetAccount(name: 'Consulting Revenue', type: AccountTypeEnum::INCOME, parent: self::OPERATING_REVENUE, code: '40103')]
    case CONSULTING_REVENUE = 'CONSULTING_REVENUE';
    case MAINTENANCE_REVENUE = 'MAINTENANCE_REVENUE';
    #[PresetAccount(name: 'Shipping Revenue', type: AccountTypeEnum::INCOME, parent: self::OPERATING_REVENUE, code: '40106')]
    case SHIPPING_REVENUE = 'SHIPPING_REVENUE';

    case PRODUCT_SALES = 'PRODUCT_SALES';
    case SOFTWARE_SALES = 'SOFTWARE_SALES';
    case MERCHANDISE_SALES = 'MERCHANDISE_SALES';

    #[PresetAccount(name: 'Rental Revenue', type: AccountTypeEnum::INCOME, parent: self::OPERATING_REVENUE, code: '40104')]

    case RENTAL_REVENUE = 'RENTAL_REVENUE';
    case ROYALTY_REVENUE = 'ROYALTY_REVENUE';
    case FRANCHISE_FEES = 'FRANCHISE_FEES';
    case LICENSE_REVENUE = 'LICENSE_REVENUE';

    // Other Revenue (6500-6799)
    #[PresetAccount(name: 'Interest Revenue', type: AccountTypeEnum::INCOME, parent: self::OTHER_REVENUE, code: '40201')]
    case INTEREST_REVENUE = 'INTEREST_REVENUE';
    #[PresetAccount(name: 'Dividend Revenue', type: AccountTypeEnum::INCOME, parent: self::OTHER_REVENUE, code: '40202')]
    case DIVIDEND_REVENUE = 'DIVIDEND_REVENUE';
    case INVESTMENT_INCOME = 'INVESTMENT_INCOME';

    #[PresetAccount(name: 'Gain on Sale of Assets', type: AccountTypeEnum::INCOME, parent: self::OTHER_REVENUE, code: '40203')]

    case GAIN_ON_SALE_OF_ASSETS = 'GAIN_ON_SALE_OF_ASSETS';
    case UNREALIZED_GAIN = 'UNREALIZED_GAIN';
    #[PresetAccount(name: 'Foreign Exchange Gain', type: AccountTypeEnum::INCOME, parent: self::OTHER_REVENUE, code: '40204')]
    case FOREIGN_EXCHANGE_GAIN = 'FOREIGN_EXCHANGE_GAIN';

    #[PresetAccount(name: 'Other Income', type: AccountTypeEnum::INCOME, parent: self::OTHER_REVENUE, code: '40205')]

    case OTHER_INCOME = 'OTHER_INCOME';
    case SUBSIDY_INCOME = 'SUBSIDY_INCOME';
    case GRANT_INCOME = 'GRANT_INCOME';

    // Contra-Revenue (6800-6899)
    #[PresetAccount(name: 'Sales Returns', type: AccountTypeEnum::INCOME, parent: self::SALES_RETURNS_AND_ALLOWANCES, code: '40901', normalBalance: NormalBalance::DEBIT, subCategory: 'Contra Revenue')]
    case SALES_RETURNS = 'SALES_RETURNS';
    #[PresetAccount(name: 'Sales Allowances', type: AccountTypeEnum::INCOME, parent: self::SALES_RETURNS_AND_ALLOWANCES, code: '40903', normalBalance: NormalBalance::DEBIT, subCategory: 'Contra Revenue')]
    case SALES_ALLOWANCES = 'SALES_ALLOWANCES';
    #[PresetAccount(name: 'Sales Discounts', type: AccountTypeEnum::INCOME, parent: self::SALES_RETURNS_AND_ALLOWANCES, code: '40902', normalBalance: NormalBalance::DEBIT, subCategory: 'Contra Revenue')]
    case SALES_DISCOUNTS = 'SALES_DISCOUNTS';
    case PURCHASE_RETURNS = 'PURCHASE_RETURNS';
    case PURCHASE_DISCOUNTS = 'PURCHASE_DISCOUNTS';

    // ====================================
    // EXPENSES (7xxx - 8xxx series)
    // ====================================

    // Cost of Goods Sold (7000-7499)
    #[PresetAccount(name: 'Cost of Goods Sold', type: AccountTypeEnum::EXPENSE, code: '50000', category: 'Cost of Sales', reportSection: 'Expenses')]
    case COST_OF_GOODS_SOLD = 'COST_OF_GOODS_SOLD';
    #[PresetAccount(name: 'Cost of Sales', type: AccountTypeEnum::EXPENSE, parent: self::COST_OF_GOODS_SOLD, code: '50001')]
    case COST_OF_SALES = 'COST_OF_SALES';
    #[PresetAccount(name: 'Cost of Services', type: AccountTypeEnum::EXPENSE, parent: self::COST_OF_GOODS_SOLD, code: '50002')]
    case COST_OF_SERVICES = 'COST_OF_SERVICES';
    case PURCHASES = 'PURCHASES';

    case MATERIALS_USED = 'MATERIALS_USED';
    #[PresetAccount(name: 'Direct Materials', type: AccountTypeEnum::EXPENSE, parent: self::COST_OF_GOODS_SOLD, code: '50004')]
    case DIRECT_MATERIALS = 'DIRECT_MATERIALS';
    #[PresetAccount(name: 'Direct Labor', type: AccountTypeEnum::EXPENSE, parent: self::COST_OF_GOODS_SOLD, code: '50003')]
    case DIRECT_LABOR = 'DIRECT_LABOR';
    #[PresetAccount(name: 'Subcontractor Costs', type: AccountTypeEnum::EXPENSE, parent: self::COST_OF_GOODS_SOLD, code: '50005')]
    case SUBCONTRACTOR_COSTS = 'SUBCONTRACTOR_COSTS';
    #[PresetAccount(name: 'Freight In', type: AccountTypeEnum::EXPENSE, parent: self::COST_OF_GOODS_SOLD, code: '50006')]
    case FREIGHT_IN = 'FREIGHT_IN';
    #[PresetAccount(name: 'Inventory Adjustments', type: AccountTypeEnum::EXPENSE, parent: self::COST_OF_GOODS_SOLD, code: '50007')]
    case INVENTORY_ADJUSTMENTS = 'INVENTORY_ADJUSTMENTS';
    #[PresetAccount(name: 'Manufacturing Overhead', type: AccountTypeEnum::EXPENSE, parent: self::COST_OF_GOODS_SOLD, code: '50008')]
    case MANUFACTURING_OVERHEAD = 'MANUFACTURING_OVERHEAD';

    // Operating Expenses (7500-7999)
    #[PresetAccount(name: 'Salaries Expense', type: AccountTypeEnum::EXPENSE, parent: self::PAYROLL_EXPENSES, code: '60101')]
    case SALARIES_EXPENSE = 'SALARIES_EXPENSE';
    #[PresetAccount(name: 'Wages Expense', type: AccountTypeEnum::EXPENSE, parent: self::PAYROLL_EXPENSES, code: '60102')]
    case WAGES_EXPENSE = 'WAGES_EXPENSE';
    #[PresetAccount(name: 'Bonus Expense', type: AccountTypeEnum::EXPENSE, parent: self::PAYROLL_EXPENSES, code: '60103')]
    case BONUS_EXPENSE = 'BONUS_EXPENSE';
    #[PresetAccount(name: 'Commission Expense', type: AccountTypeEnum::EXPENSE, parent: self::PAYROLL_EXPENSES, code: '60104')]
    case COMMISSION_EXPENSE = 'COMMISSION_EXPENSE';
    #[PresetAccount(name: 'Payroll Taxes Expense', type: AccountTypeEnum::EXPENSE, parent: self::PAYROLL_EXPENSES, code: '60105')]
    case PAYROLL_TAX_EXPENSE = 'PAYROLL_TAX_EXPENSE';
    #[PresetAccount(name: 'Employee Benefits Expense', type: AccountTypeEnum::EXPENSE, parent: self::PAYROLL_EXPENSES, code: '60106')]
    case EMPLOYEE_BENEFITS_EXPENSE = 'EMPLOYEE_BENEFITS_EXPENSE';

    #[PresetAccount(name: 'Office Expenses', type: AccountTypeEnum::EXPENSE, parent: self::OPERATING_EXPENSES, code: '60300', allowsManualEntries: false)]

    case OFFICE_EXPENSES = 'OFFICE_EXPENSES';
    #[PresetAccount(name: 'Rent Expense', type: AccountTypeEnum::EXPENSE, parent: self::OCCUPANCY_EXPENSES, code: '60201')]
    case RENT_EXPENSE = 'RENT_EXPENSE';
    case LEASE_EXPENSE = 'LEASE_EXPENSE';
    #[PresetAccount(name: 'Utilities Expense', type: AccountTypeEnum::EXPENSE, parent: self::OCCUPANCY_EXPENSES, code: '60202')]
    case UTILITIES_EXPENSE = 'UTILITIES_EXPENSE';
    #[PresetAccount(name: 'Occupancy Expenses', type: AccountTypeEnum::EXPENSE, parent: self::OPERATING_EXPENSES, code: '60200', allowsManualEntries: false)]
    case OCCUPANCY_EXPENSES = 'OCCUPANCY_EXPENSES';

    #[PresetAccount(name: 'Office Supplies Expense', type: AccountTypeEnum::EXPENSE, parent: self::OFFICE_EXPENSES, code: '60301')]

    case OFFICE_SUPPLIES_EXPENSE = 'OFFICE_SUPPLIES_EXPENSE';
    #[PresetAccount(name: 'Postage Expense', type: AccountTypeEnum::EXPENSE, parent: self::OFFICE_EXPENSES, code: '60302')]
    case POSTAGE_EXPENSE = 'POSTAGE_EXPENSE';
    #[PresetAccount(name: 'Printing Expense', type: AccountTypeEnum::EXPENSE, parent: self::OFFICE_EXPENSES, code: '60303')]
    case PRINTING_EXPENSE = 'PRINTING_EXPENSE';
    #[PresetAccount(name: 'Telephone Expense', type: AccountTypeEnum::EXPENSE, parent: self::OFFICE_EXPENSES, code: '60305')]
    case TELEPHONE_EXPENSE = 'TELEPHONE_EXPENSE';
    #[PresetAccount(name: 'Internet Expense', type: AccountTypeEnum::EXPENSE, parent: self::OFFICE_EXPENSES, code: '60306')]
    case INTERNET_EXPENSE = 'INTERNET_EXPENSE';
    #[PresetAccount(name: 'Shipping Expense', type: AccountTypeEnum::EXPENSE, parent: self::TRAVEL_EXPENSE, code: '60603')]
    case SHIPPING_EXPENSE = 'SHIPPING_EXPENSE';
    case TECHNOLOGY_EXPENSE = 'TECHNOLOGY_EXPENSE';
    case LICENSES_AND_PERMITS = 'LICENSES_AND_PERMITS';
    case PROPERTY_TAX_EXPENSE = 'PROPERTY_TAX_EXPENSE';
    #[PresetAccount(name: 'Merchant Services Fees', type: AccountTypeEnum::EXPENSE, parent: self::FINANCIAL_EXPENSES, code: '70006')]
    case MERCHANT_SERVICES_FEES = 'MERCHANT_SERVICES_FEES';
    case PAYROLL_PROCESSING_FEES = 'PAYROLL_PROCESSING_FEES';

    #[PresetAccount(name: 'Travel Expense', type: AccountTypeEnum::EXPENSE, parent: self::OPERATING_EXPENSES, code: '60600', allowsManualEntries: false)]

    case TRAVEL_EXPENSE = 'TRAVEL_EXPENSE';
    #[PresetAccount(name: 'Meals and Entertainment', type: AccountTypeEnum::EXPENSE, parent: self::TRAVEL_EXPENSE, code: '60601')]
    case MEALS_AND_ENTERTAINMENT = 'MEALS_AND_ENTERTAINMENT';
    #[PresetAccount(name: 'Vehicle Expense', type: AccountTypeEnum::EXPENSE, parent: self::TRAVEL_EXPENSE, code: '60602')]
    case VEHICLE_EXPENSE = 'VEHICLE_EXPENSE';

    #[PresetAccount(name: 'Advertising Expense', type: AccountTypeEnum::EXPENSE, parent: self::MARKETING_SALES_EXPENSES, code: '60401')]

    case ADVERTISING_EXPENSE = 'ADVERTISING_EXPENSE';
    #[PresetAccount(name: 'Marketing Expense', type: AccountTypeEnum::EXPENSE, parent: self::MARKETING_SALES_EXPENSES, code: '60402')]
    case MARKETING_EXPENSE = 'MARKETING_EXPENSE';
    #[PresetAccount(name: 'Marketing & Sales Expenses', type: AccountTypeEnum::EXPENSE, parent: self::OPERATING_EXPENSES, code: '60400', allowsManualEntries: false)]
    case MARKETING_SALES_EXPENSES = 'MARKETING_SALES_EXPENSES';
    case PROMOTION_EXPENSE = 'PROMOTION_EXPENSE';

    #[PresetAccount(name: 'Professional Fees', type: AccountTypeEnum::EXPENSE, parent: self::OPERATING_EXPENSES, code: '60500')]

    case PROFESSIONAL_FEES = 'PROFESSIONAL_FEES';
    #[PresetAccount(name: 'Legal Fees', type: AccountTypeEnum::EXPENSE, parent: self::PROFESSIONAL_FEES, code: '60501')]
    case LEGAL_FEES = 'LEGAL_FEES';
    #[PresetAccount(name: 'Accounting Fees', type: AccountTypeEnum::EXPENSE, parent: self::PROFESSIONAL_FEES, code: '60502')]
    case ACCOUNTING_FEES = 'ACCOUNTING_FEES';
    #[PresetAccount(name: 'Consulting Fees', type: AccountTypeEnum::EXPENSE, parent: self::PROFESSIONAL_FEES, code: '60503')]
    case CONSULTING_FEES = 'CONSULTING_FEES';

    #[PresetAccount(name: 'Insurance Expense', type: AccountTypeEnum::EXPENSE, parent: self::OPERATING_EXPENSES, code: '60700')]

    case INSURANCE_EXPENSE = 'INSURANCE_EXPENSE';
    #[PresetAccount(name: 'Repairs and Maintenance', type: AccountTypeEnum::EXPENSE, parent: self::OCCUPANCY_EXPENSES, code: '60203')]
    case REPAIRS_AND_MAINTENANCE = 'REPAIRS_AND_MAINTENANCE';

    #[PresetAccount(name: 'Depreciation Expense', type: AccountTypeEnum::EXPENSE, parent: self::OPERATING_EXPENSES, code: '60800')]

    case DEPRECIATION_EXPENSE = 'DEPRECIATION_EXPENSE';
    #[PresetAccount(name: 'Amortization Expense', type: AccountTypeEnum::EXPENSE, parent: self::OPERATING_EXPENSES, code: '60810')]
    case AMORTIZATION_EXPENSE = 'AMORTIZATION_EXPENSE';
    case DEPLETION_EXPENSE = 'DEPLETION_EXPENSE';

    // Financial Expenses (8000-8199)
    #[PresetAccount(name: 'Financial Expenses', type: AccountTypeEnum::EXPENSE, code: '70000', category: 'Non-Operating Expenses', reportSection: 'Expenses', allowsManualEntries: false)]
    case FINANCIAL_EXPENSES = 'FINANCIAL_EXPENSES';
    #[PresetAccount(name: 'Interest Expense', type: AccountTypeEnum::EXPENSE, parent: self::FINANCIAL_EXPENSES, code: '70001')]
    case INTEREST_EXPENSE = 'INTEREST_EXPENSE';
    #[PresetAccount(name: 'Bank Charges', type: AccountTypeEnum::EXPENSE, parent: self::FINANCIAL_EXPENSES, code: '70002')]
    case BANK_CHARGES = 'BANK_CHARGES';
    #[PresetAccount(name: 'Credit Card Fees', type: AccountTypeEnum::EXPENSE, parent: self::FINANCIAL_EXPENSES, code: '70005')]
    case CREDIT_CARD_FEES = 'CREDIT_CARD_FEES';
    case LOAN_FEES = 'LOAN_FEES';

    #[PresetAccount(name: 'Foreign Exchange Loss', type: AccountTypeEnum::EXPENSE, parent: self::FINANCIAL_EXPENSES, code: '70003')]

    case FOREIGN_EXCHANGE_LOSS = 'FOREIGN_EXCHANGE_LOSS';
    #[PresetAccount(name: 'Loss on Sale of Assets', type: AccountTypeEnum::EXPENSE, parent: self::FINANCIAL_EXPENSES, code: '70004')]
    case LOSS_ON_SALE_OF_ASSETS = 'LOSS_ON_SALE_OF_ASSETS';
    case UNREALIZED_LOSS = 'UNREALIZED_LOSS';
    case IMPAIRMENT_LOSS = 'IMPAIRMENT_LOSS';

    // Tax Expenses (8200-8299)
    #[PresetAccount(name: 'Income Tax Expense', type: AccountTypeEnum::EXPENSE, code: '70100', category: 'Tax Expenses', reportSection: 'Expenses')]
    case INCOME_TAX_EXPENSE = 'INCOME_TAX_EXPENSE';
    case DEFERRED_TAX_EXPENSE = 'DEFERRED_TAX_EXPENSE';

    // Other Expenses (8300-8999)
    #[PresetAccount(name: 'Bad Debt Expense', type: AccountTypeEnum::EXPENSE, parent: self::OTHER_EXPENSES, code: '70201')]
    case BAD_DEBT_EXPENSE = 'BAD_DEBT_EXPENSE';
    #[PresetAccount(name: 'Donations', type: AccountTypeEnum::EXPENSE, parent: self::OTHER_EXPENSES, code: '70203')]
    case DONATIONS = 'DONATIONS';
    #[PresetAccount(name: 'Penalties and Fines', type: AccountTypeEnum::EXPENSE, parent: self::OTHER_EXPENSES, code: '70204')]
    case PENALTIES_AND_FINES = 'PENALTIES_AND_FINES';
    #[PresetAccount(name: 'Other Expenses', type: AccountTypeEnum::EXPENSE, code: '70200', category: 'Other Expenses', reportSection: 'Expenses', allowsManualEntries: false)]
    case OTHER_EXPENSES = 'OTHER_EXPENSES';

    #[PresetAccount(name: 'Research and Development', type: AccountTypeEnum::EXPENSE, parent: self::OPERATING_EXPENSES, code: '60900')]

    case RESEARCH_AND_DEVELOPMENT = 'RESEARCH_AND_DEVELOPMENT';
    #[PresetAccount(name: 'Training and Development', type: AccountTypeEnum::EXPENSE, parent: self::PAYROLL_EXPENSES, code: '60107')]
    case TRAINING_EXPENSE = 'TRAINING_EXPENSE';
    #[PresetAccount(name: 'Software & SaaS Subscription', type: AccountTypeEnum::EXPENSE, parent: self::OFFICE_EXPENSES, code: '60307')]
    case SUBSCRIPTION_EXPENSE = 'SUBSCRIPTION_EXPENSE';
    #[PresetAccount(name: 'Dues and Memberships', type: AccountTypeEnum::EXPENSE, parent: self::OFFICE_EXPENSES, code: '60308')]
    case DUES_AND_MEMBERSHIPS = 'DUES_AND_MEMBERSHIPS';
    #[PresetAccount(name: 'Taxes and Licenses', type: AccountTypeEnum::EXPENSE, parent: self::OFFICE_EXPENSES, code: '60309')]
    case TAXES_AND_LICENSES = 'TAXES_AND_LICENSES';

    // ====================================
    // TAX ACCOUNTS (Special)
    // ====================================
    #[PresetAccount(name: 'Input VAT', type: AccountTypeEnum::ASSET, parent: self::CURRENT_ASSETS, code: '10500')]
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
    #[PresetAccount(name: 'Property, Plant & Equipment', type: AccountTypeEnum::ASSET, parent: self::NON_CURRENT_ASSETS, code: '15100', allowsManualEntries: false)]
    case FIXED_ASSETS = 'FIXED_ASSETS';
    #[PresetAccount(name: 'Operating Expenses', type: AccountTypeEnum::EXPENSE, code: '60000', category: 'Operating Expenses', reportSection: 'Expenses', allowsManualEntries: false, isSystemAccount: true)]
    case OPERATING_EXPENSES = 'OPERATING_EXPENSES'; // Parent
    case EXPENSES = 'EXPENSES'; // Legacy alias
    #[PresetAccount(name: 'Payroll Expenses', type: AccountTypeEnum::EXPENSE, parent: self::OPERATING_EXPENSES, code: '60100', allowsManualEntries: false)]
    case PAYROLL_EXPENSES = 'PAYROLL_EXPENSES'; // Parent
    #[PresetAccount(name: 'Loan Payable', type: AccountTypeEnum::LIABILITY, parent: self::LONG_TERM_LIABILITIES, code: '25006')]
    case LOAN_PAYABLE = 'LOAN_PAYABLE';

    /**
     * Get account type (Asset, Liability, Equity, Revenue, Expense)
     */
    public function type(): AccountTypeEnum
    {
        // A preset of the seeded chart takes its type from its definition.
        if ($definition = PresetDefinitions::of($this)) {
            return $definition->type;
        }

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
        if ($normalBalance = PresetDefinitions::of($this)?->normalBalance) {
            return $normalBalance;
        }

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
            static fn ($preset) => $preset->type() === $type
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
                self::MERCHANDISE_INVENTORY,
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
            ],
        ];

        return $categories[$category] ?? [];
    }

    /**
     * These presets should never be available for account creation.
     * That means we should not allow users to create accounts with these presets.
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
            self::CASH_AND_CASH_EQUIVALENT,
        ]);
    }

    /**
     * The presets each industry's chart of accounts is seeded with: every
     * preset, minus the ones listed for that industry below ('full' keeps
     * them all).
     *
     * @return array<string, AccountPreset[]>
     */
    public static function byIndustry(): array
    {
        /** @var array<string, AccountPreset[]> $excluded */
        $excluded = [
            'full' => [],
            'retail' => [],
            'saas' => [],
            'manufacturing' => [],
        ];

        return array_map(
            static fn (array $exclude) => array_values(array_filter(
                self::cases(),
                static fn (self $preset) => ! in_array($preset, $exclude, true)
            )),
            $excluded
        );
    }
}
