<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\AccountType as AccountTypeModel;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Implements a comprehensive Chart of Accounts based on IFRS/GAAP standards
     * with hierarchical structure and all common accounts across industries.
     */
    public function run(): void
    {
        // Ensure account types exist
        $accountTypes = $this->getAccountTypes();

        // Create hierarchical chart of accounts
        $this->seedAssets($accountTypes);
        $this->seedLiabilities($accountTypes);
        $this->seedEquity($accountTypes);
        $this->seedRevenue($accountTypes);
        $this->seedExpenses($accountTypes);
    }

    /**
     * Get account types from database
     */
    protected function getAccountTypes(): array
    {
        return [
            'Asset' => AccountTypeModel::where('name', 'ASSET')->first(),
            'Liability' => AccountTypeModel::where('name', 'LIABILITY')->first(),
            'Equity' => AccountTypeModel::where('name', 'EQUITY')->first(),
            'Income' => AccountTypeModel::where('name', 'INCOME')->first(),
            'Expense' => AccountTypeModel::where('name', 'EXPENSE')->first(),
        ];
    }

    /**
     * Seed Asset Accounts (1000-2999)
     */
    protected function seedAssets(array $accountTypes): void
    {
        $assetType = $accountTypes['Asset'];
        $assetCode = $assetType->code;

        // 1000 - Current Assets (Parent)
        $currentAssets = $this->createAccount([
            'code' => $assetCode,
            'name' => 'Current Assets',
            'account_type_id' => $assetType->id,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'is_system_account' => true,
            'allows_manual_entries' => false,
        ]);

        // 1010 - Cash and Cash Equivalents
        $cashParent = $this->createAccount([
            'code' => '1010',
            'name' => 'Cash and Cash Equivalents',
            'account_type_id' => $assetType->id,
            'parent_id' => $currentAssets->id,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '1011',
            'name' => 'Cash',
            'account_type_id' => $assetType->id,
            'parent_id' => $cashParent->id,
            'preset' => AccountPreset::CASH->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '1012',
            'name' => 'Petty Cash',
            'account_type_id' => $assetType->id,
            'parent_id' => $cashParent->id,
            'preset' => AccountPreset::PETTY_CASH->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '1013',
            'name' => 'Cash in Bank',
            'account_type_id' => $assetType->id,
            'parent_id' => $cashParent->id,
            'preset' => AccountPreset::CASH_IN_BANK->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '1014',
            'name' => 'Undeposited Funds',
            'account_type_id' => $assetType->id,
            'parent_id' => $cashParent->id,
            'preset' => AccountPreset::UNDEPOSITED_FUNDS->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        // 1100 - Accounts Receivable
        $receivablesParent = $this->createAccount([
            'code' => '1100',
            'name' => 'Accounts Receivable',
            'account_type_id' => $assetType->id,
            'parent_id' => $currentAssets->id,
            'preset' => AccountPreset::ACCOUNTS_RECEIVABLE->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '1101',
            'name' => 'Trade Receivables',
            'account_type_id' => $assetType->id,
            'parent_id' => $receivablesParent->id,
            'preset' => AccountPreset::TRADE_RECEIVABLES->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '1102',
            'name' => 'Notes Receivable',
            'account_type_id' => $assetType->id,
            'parent_id' => $receivablesParent->id,
            'preset' => AccountPreset::NOTES_RECEIVABLE->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '1110',
            'name' => 'Allowance for Doubtful Accounts',
            'account_type_id' => $assetType->id,
            'parent_id' => $receivablesParent->id,
            'preset' => AccountPreset::ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS->value,
            'category' => 'Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
        ]);

        // 1200 - Inventory
        $inventoryParent = $this->createAccount([
            'code' => '1200',
            'name' => 'Inventory',
            'account_type_id' => $assetType->id,
            'parent_id' => $currentAssets->id,
            'preset' => AccountPreset::INVENTORY->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '1201',
            'name' => 'Raw Materials',
            'account_type_id' => $assetType->id,
            'parent_id' => $inventoryParent->id,
            'preset' => AccountPreset::RAW_MATERIALS->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '1202',
            'name' => 'Work in Progress',
            'account_type_id' => $assetType->id,
            'parent_id' => $inventoryParent->id,
            'preset' => AccountPreset::WORK_IN_PROGRESS->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '1203',
            'name' => 'Finished Goods',
            'account_type_id' => $assetType->id,
            'parent_id' => $inventoryParent->id,
            'preset' => AccountPreset::FINISHED_GOODS->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '1204',
            'name' => 'Merchandise Inventory',
            'account_type_id' => $assetType->id,
            'parent_id' => $inventoryParent->id,
            'preset' => AccountPreset::MERCHANDISE_INVENTORY->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        // 1300 - Prepaid Expenses
        $prepaidParent = $this->createAccount([
            'code' => '1300',
            'name' => 'Prepaid Expenses',
            'account_type_id' => $assetType->id,
            'parent_id' => $currentAssets->id,
            'preset' => AccountPreset::PREPAID_EXPENSES->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '1301',
            'name' => 'Prepaid Insurance',
            'account_type_id' => $assetType->id,
            'parent_id' => $prepaidParent->id,
            'preset' => AccountPreset::PREPAID_INSURANCE->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '1302',
            'name' => 'Prepaid Rent',
            'account_type_id' => $assetType->id,
            'parent_id' => $prepaidParent->id,
            'preset' => AccountPreset::PREPAID_RENT->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '1303',
            'name' => 'Prepaid Subscriptions',
            'account_type_id' => $assetType->id,
            'parent_id' => $prepaidParent->id,
            'preset' => AccountPreset::PREPAID_SUBSCRIPTIONS->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '1305',
            'name' => 'Advance to Suppliers',
            'account_type_id' => $assetType->id,
            'parent_id' => $prepaidParent->id,
            'preset' => AccountPreset::ADVANCE_TO_SUPPLIERS->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '1306',
            'name' => 'Advance to Employees',
            'account_type_id' => $assetType->id,
            'parent_id' => $prepaidParent->id,
            'preset' => AccountPreset::ADVANCE_TO_EMPLOYEES->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        // 1400 - Tax Assets
        $this->createAccount([
            'code' => '1400',
            'name' => 'Input VAT',
            'account_type_id' => $assetType->id,
            'parent_id' => $currentAssets->id,
            'preset' => AccountPreset::INPUT_VAT->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        // 1500 - Short-term Investments
        $this->createAccount([
            'code' => '1500',
            'name' => 'Short-term Investments',
            'account_type_id' => $assetType->id,
            'parent_id' => $currentAssets->id,
            'preset' => AccountPreset::SHORT_TERM_INVESTMENTS->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
        ]);

        // 2000 - Non-Current Assets (Parent)
        $nonCurrentAssets = $this->createAccount([
            'code' => '2000',
            'name' => 'Non-Current Assets',
            'account_type_id' => $assetType->id,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'is_system_account' => true,
            'allows_manual_entries' => false,
        ]);

        // 2100 - Property, Plant & Equipment
        $ppeParent = $this->createAccount([
            'code' => '2100',
            'name' => 'Property, Plant & Equipment',
            'account_type_id' => $assetType->id,
            'parent_id' => $nonCurrentAssets->id,
            'category' => 'Non-Current Assets',
            'preset' => AccountPreset::FIXED_ASSETS->value,
            'report_section' => 'Assets',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '2101',
            'name' => 'Land',
            'account_type_id' => $assetType->id,
            'parent_id' => $ppeParent->id,
            'preset' => AccountPreset::LAND->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '2102',
            'name' => 'Buildings',
            'account_type_id' => $assetType->id,
            'parent_id' => $ppeParent->id,
            'preset' => AccountPreset::BUILDINGS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '2103',
            'name' => 'Machinery and Equipment',
            'account_type_id' => $assetType->id,
            'parent_id' => $ppeParent->id,
            'preset' => AccountPreset::MACHINERY_AND_EQUIPMENT->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '2104',
            'name' => 'Office Equipment',
            'account_type_id' => $assetType->id,
            'parent_id' => $ppeParent->id,
            'preset' => AccountPreset::OFFICE_EQUIPMENT->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '2105',
            'name' => 'Furniture and Fixtures',
            'account_type_id' => $assetType->id,
            'parent_id' => $ppeParent->id,
            'preset' => AccountPreset::FURNITURE_AND_FIXTURES->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '2106',
            'name' => 'Vehicles',
            'account_type_id' => $assetType->id,
            'parent_id' => $ppeParent->id,
            'preset' => AccountPreset::VEHICLES->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
        ]);

        // 2200 - Accumulated Depreciation (Contra Asset)
        $accumDepreciation = $this->createAccount([
            'code' => '2200',
            'name' => 'Accumulated Depreciation',
            'account_type_id' => $assetType->id,
            'parent_id' => $nonCurrentAssets->id,
            'preset' => AccountPreset::ACCUMULATED_DEPRECIATION->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '2201',
            'name' => 'Accumulated Depreciation - Buildings',
            'account_type_id' => $assetType->id,
            'parent_id' => $accumDepreciation->id,
            'preset' => AccountPreset::ACCUM_DEPR_BUILDINGS->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '2202',
            'name' => 'Accumulated Depreciation - Equipment',
            'account_type_id' => $assetType->id,
            'parent_id' => $accumDepreciation->id,
            'preset' => AccountPreset::ACCUM_DEPR_EQUIPMENT->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '2203',
            'name' => 'Accumulated Depreciation - Vehicles',
            'account_type_id' => $assetType->id,
            'parent_id' => $accumDepreciation->id,
            'preset' => AccountPreset::ACCUM_DEPR_VEHICLES->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '2204',
            'name' => 'Accumulated Depreciation - Furniture',
            'account_type_id' => $assetType->id,
            'parent_id' => $accumDepreciation->id,
            'preset' => AccountPreset::ACCUM_DEPR_FURNITURE->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
        ]);

        // 2300 - Intangible Assets
        $intangibleParent = $this->createAccount([
            'code' => '2300',
            'name' => 'Intangible Assets',
            'account_type_id' => $assetType->id,
            'parent_id' => $nonCurrentAssets->id,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '2301',
            'name' => 'Goodwill',
            'account_type_id' => $assetType->id,
            'parent_id' => $intangibleParent->id,
            'preset' => AccountPreset::GOODWILL->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '2302',
            'name' => 'Patents',
            'account_type_id' => $assetType->id,
            'parent_id' => $intangibleParent->id,
            'preset' => AccountPreset::PATENTS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '2303',
            'name' => 'Software',
            'account_type_id' => $assetType->id,
            'parent_id' => $intangibleParent->id,
            'preset' => AccountPreset::SOFTWARE->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '2304',
            'name' => 'Trademarks',
            'account_type_id' => $assetType->id,
            'parent_id' => $intangibleParent->id,
            'preset' => AccountPreset::TRADEMARKS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '2305',
            'name' => 'Copyrights',
            'account_type_id' => $assetType->id,
            'parent_id' => $intangibleParent->id,
            'preset' => AccountPreset::COPYRIGHTS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
        ]);

        $this->createAccount([
            'code' => '2310',
            'name' => 'Accumulated Amortization',
            'account_type_id' => $assetType->id,
            'parent_id' => $intangibleParent->id,
            'preset' => AccountPreset::ACCUMULATED_AMORTIZATION->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
        ]);

        // 2400 - Long-term Investments
        $this->createAccount([
            'code' => '2400',
            'name' => 'Long-term Investments',
            'account_type_id' => $assetType->id,
            'parent_id' => $nonCurrentAssets->id,
            'preset' => AccountPreset::LONG_TERM_INVESTMENTS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
        ]);
    }

    /**
     * Seed Liability Accounts (3000-3999)
     */
    protected function seedLiabilities(array $accountTypes): void
    {
        $liabilityType = $accountTypes['Liability'];

        // 3000 - Current Liabilities (Parent)
        $currentLiabilities = $this->createAccount([
            'code' => '3000',
            'name' => 'Current Liabilities',
            'account_type_id' => $liabilityType->id,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'is_system_account' => true,
            'allows_manual_entries' => false,
        ]);

        // 3010 - Accounts Payable
        $payablesParent = $this->createAccount([
            'code' => '3010',
            'name' => 'Accounts Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::ACCOUNTS_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3011',
            'name' => 'Trade Payables',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payablesParent->id,
            'preset' => AccountPreset::TRADE_PAYABLES->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3012',
            'name' => 'Notes Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payablesParent->id,
            'preset' => AccountPreset::NOTES_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        // 3100 - Accrued Expenses
        $accruedParent = $this->createAccount([
            'code' => '3100',
            'name' => 'Accrued Expenses',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::ACCRUED_EXPENSES->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3101',
            'name' => 'Accrued Salaries',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $accruedParent->id,
            'preset' => AccountPreset::ACCRUED_SALARIES->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3102',
            'name' => 'Accrued Interest',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $accruedParent->id,
            'preset' => AccountPreset::ACCRUED_INTEREST->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3103',
            'name' => 'Accrued Utilities',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $accruedParent->id,
            'preset' => AccountPreset::ACCRUED_UTILITIES->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        // 3200 - Short-term Debt
        $this->createAccount([
            'code' => '3200',
            'name' => 'Short-term Loans',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::SHORT_TERM_LOANS->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3201',
            'name' => 'Current Portion of Long-term Debt',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::CURRENT_PORTION_OF_LONG_TERM_DEBT->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3202',
            'name' => 'Line of Credit',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::LINE_OF_CREDIT->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        // 3300 - Unearned Revenue
        $this->createAccount([
            'code' => '3300',
            'name' => 'Unearned Revenue',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::UNEARNED_REVENUE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3301',
            'name' => 'Customer Deposits',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::CUSTOMER_DEPOSITS->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3302',
            'name' => 'Deferred Revenue',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::DEFERRED_REVENUE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3303',
            'name' => 'Advances from Customers',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::ADVANCES_FROM_CUSTOMERS->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        // 3400 - Tax Liabilities
        $taxParent = $this->createAccount([
            'code' => '3400',
            'name' => 'Tax Liabilities',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '3401',
            'name' => 'Sales Tax Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $taxParent->id,
            'preset' => AccountPreset::SALES_TAX_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3402',
            'name' => 'VAT Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $taxParent->id,
            'preset' => AccountPreset::VAT_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3403',
            'name' => 'Income Tax Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $taxParent->id,
            'preset' => AccountPreset::INCOME_TAX_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3404',
            'name' => 'GST Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $taxParent->id,
            'preset' => AccountPreset::GST_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3405',
            'name' => 'Payroll Tax Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $taxParent->id,
            'preset' => AccountPreset::PAYROLL_TAX_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        // 3500 - Payroll Liabilities
        $payrollParent = $this->createAccount([
            'code' => '3500',
            'name' => 'Payroll Liabilities',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '3501',
            'name' => 'Wages Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::WAGES_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3502',
            'name' => 'Salaries Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::SALARIES_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3503',
            'name' => 'Bonuses Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::BONUSES_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3506',
            'name' => 'Vacation Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::VACATION_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3510',
            'name' => 'Payroll Clearing',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::PAYROLL_CLEARING->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3511',
            'name' => 'Federal Withholding Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::FEDERAL_WITHHOLDING_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3512',
            'name' => 'Social Security Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::SOCIAL_SECURITY_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3513',
            'name' => 'Medicare Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::MEDICARE_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
        ]);

        // 3600 - Long-term Liabilities (Parent)
        $longTermLiabilities = $this->createAccount([
            'code' => '3600',
            'name' => 'Long-term Liabilities',
            'account_type_id' => $liabilityType->id,
            'category' => 'Long-term Liabilities',
            'report_section' => 'Liabilities',
            'is_system_account' => true,
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '3601',
            'name' => 'Long-term Debt',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $longTermLiabilities->id,
            'preset' => AccountPreset::LONG_TERM_DEBT->value,
            'category' => 'Long-term Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3602',
            'name' => 'Bonds Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $longTermLiabilities->id,
            'preset' => AccountPreset::BONDS_PAYABLE->value,
            'category' => 'Long-term Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3603',
            'name' => 'Mortgage Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $longTermLiabilities->id,
            'preset' => AccountPreset::MORTGAGE_PAYABLE->value,
            'category' => 'Long-term Liabilities',
            'report_section' => 'Liabilities',
        ]);

        $this->createAccount([
            'code' => '3604',
            'name' => 'Lease Liabilities',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $longTermLiabilities->id,
            'preset' => AccountPreset::LEASE_LIABILITIES->value,
            'category' => 'Long-term Liabilities',
            'report_section' => 'Liabilities',
        ]);
    }

    /**
     * Seed Equity Accounts (4000-4999)
     */
    protected function seedEquity(array $accountTypes): void
    {
        $equityType = $accountTypes['Equity'];

        // 4000 - Equity (Parent)
        $equity = $this->createAccount([
            'code' => '4000',
            'name' => 'Equity',
            'account_type_id' => $equityType->id,
            'category' => 'Equity',
            'report_section' => 'Equity',
            'is_system_account' => true,
            'allows_manual_entries' => false,
        ]);

        // 4010 - Share Capital
        $this->createAccount([
            'code' => '4010',
            'name' => 'Common Stock',
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::COMMON_STOCK->value,
            'category' => 'Equity',
            'report_section' => 'Equity',
        ]);

        $this->createAccount([
            'code' => '4020',
            'name' => 'Additional Paid-in Capital',
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::ADDITIONAL_PAID_IN_CAPITAL->value,
            'category' => 'Equity',
            'report_section' => 'Equity',
        ]);

        $this->createAccount([
            'code' => '4030',
            'name' => 'Preferred Stock',
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::PREFERRED_STOCK->value,
            'category' => 'Equity',
            'report_section' => 'Equity',
        ]);

        // 4100 - Retained Earnings
        $this->createAccount([
            'code' => '4100',
            'name' => 'Retained Earnings',
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::RETAINED_EARNINGS->value,
            'category' => 'Equity',
            'report_section' => 'Equity',
            'is_system_account' => true,
        ]);

        $this->createAccount([
            'code' => '4110',
            'name' => 'Current Year Earnings',
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::CURRENT_YEAR_EARNINGS->value,
            'category' => 'Equity',
            'report_section' => 'Equity',
            'is_system_account' => true,
        ]);

        // 4200 - Owner's Equity (for sole proprietorships/partnerships)
        $this->createAccount([
            'code' => '4200',
            'name' => "Owner's Equity",
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::OWNERS_EQUITY->value,
            'category' => 'Equity',
            'report_section' => 'Equity',
        ]);

        $this->createAccount([
            'code' => '4210',
            'name' => "Owner's Draw",
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::OWNERS_DRAW->value,
            'category' => 'Equity',
            'sub_category' => 'Contra Equity',
            'report_section' => 'Equity',
        ]);

        // 4300 - Dividends
        $this->createAccount([
            'code' => '4300',
            'name' => 'Dividends Declared',
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::DIVIDENDS_DECLARED->value,
            'category' => 'Equity',
            'sub_category' => 'Contra Equity',
            'report_section' => 'Equity',
        ]);
    }

    /**
     * Seed Revenue Accounts (5000-5999)
     */
    protected function seedRevenue(array $accountTypes): void
    {
        $incomeType = $accountTypes['Income'];

        // 5000 - Revenue (Parent)
        $revenue = $this->createAccount([
            'code' => '5000',
            'name' => 'Revenue',
            'account_type_id' => $incomeType->id,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'is_system_account' => true,
            'allows_manual_entries' => false,
        ]);

        // 5010 - Operating Revenue
        $operatingRevenue = $this->createAccount([
            'code' => '5010',
            'name' => 'Operating Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $revenue->id,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '5011',
            'name' => 'Sales Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $operatingRevenue->id,
            'preset' => AccountPreset::SALES_REVENUE->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
        ]);

        $this->createAccount([
            'code' => '5012',
            'name' => 'Service Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $operatingRevenue->id,
            'preset' => AccountPreset::SERVICE_REVENUE->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
        ]);

        $this->createAccount([
            'code' => '5013',
            'name' => 'Consulting Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $operatingRevenue->id,
            'preset' => AccountPreset::CONSULTING_REVENUE->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
        ]);

        $this->createAccount([
            'code' => '5014',
            'name' => 'Rental Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $operatingRevenue->id,
            'preset' => AccountPreset::RENTAL_REVENUE->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
        ]);

        // 5100 - Other Revenue
        $otherRevenue = $this->createAccount([
            'code' => '5100',
            'name' => 'Other Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $revenue->id,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '5101',
            'name' => 'Interest Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $otherRevenue->id,
            'preset' => AccountPreset::INTEREST_REVENUE->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
        ]);

        $this->createAccount([
            'code' => '5102',
            'name' => 'Dividend Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $otherRevenue->id,
            'preset' => AccountPreset::DIVIDEND_REVENUE->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
        ]);

        $this->createAccount([
            'code' => '5103',
            'name' => 'Gain on Sale of Assets',
            'account_type_id' => $incomeType->id,
            'parent_id' => $otherRevenue->id,
            'preset' => AccountPreset::GAIN_ON_SALE_OF_ASSETS->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
        ]);

        $this->createAccount([
            'code' => '5104',
            'name' => 'Foreign Exchange Gain',
            'account_type_id' => $incomeType->id,
            'parent_id' => $otherRevenue->id,
            'preset' => AccountPreset::FOREIGN_EXCHANGE_GAIN->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
        ]);

        $this->createAccount([
            'code' => '5105',
            'name' => 'Other Income',
            'account_type_id' => $incomeType->id,
            'parent_id' => $otherRevenue->id,
            'preset' => AccountPreset::OTHER_INCOME->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
        ]);

        // 5900 - Contra Revenue
        $contraRevenue = $this->createAccount([
            'code' => '5900',
            'name' => 'Sales Returns and Allowances',
            'account_type_id' => $incomeType->id,
            'parent_id' => $revenue->id,
            'category' => 'Revenue',
            'sub_category' => 'Contra Revenue',
            'report_section' => 'Income',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '5901',
            'name' => 'Sales Returns',
            'account_type_id' => $incomeType->id,
            'parent_id' => $contraRevenue->id,
            'preset' => AccountPreset::SALES_RETURNS->value,
            'category' => 'Revenue',
            'sub_category' => 'Contra Revenue',
            'report_section' => 'Income',
        ]);

        $this->createAccount([
            'code' => '5902',
            'name' => 'Sales Discounts',
            'account_type_id' => $incomeType->id,
            'parent_id' => $contraRevenue->id,
            'preset' => AccountPreset::SALES_DISCOUNTS->value,
            'category' => 'Revenue',
            'sub_category' => 'Contra Revenue',
            'report_section' => 'Income',
        ]);

        $this->createAccount([
            'code' => '5903',
            'name' => 'Sales Allowances',
            'account_type_id' => $incomeType->id,
            'parent_id' => $contraRevenue->id,
            'preset' => AccountPreset::SALES_ALLOWANCES->value,
            'category' => 'Revenue',
            'sub_category' => 'Contra Revenue',
            'report_section' => 'Income',
        ]);
    }

    /**
     * Seed Expense Accounts (6000-8999)
     */
    protected function seedExpenses(array $accountTypes): void
    {
        $expenseType = $accountTypes['Expense'];

        // 6000 - Cost of Goods Sold (Parent)
        $cogs = $this->createAccount([
            'code' => '6000',
            'name' => 'Cost of Goods Sold',
            'account_type_id' => $expenseType->id,
            'preset' => AccountPreset::COST_OF_GOODS_SOLD->value,
            'category' => 'Cost of Sales',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '6001',
            'name' => 'Cost of Sales',
            'account_type_id' => $expenseType->id,
            'parent_id' => $cogs->id,
            'preset' => AccountPreset::COST_OF_SALES->value,
            'category' => 'Cost of Sales',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '6002',
            'name' => 'Cost of Services',
            'account_type_id' => $expenseType->id,
            'parent_id' => $cogs->id,
            'preset' => AccountPreset::COST_OF_SERVICES->value,
            'category' => 'Cost of Sales',
            'report_section' => 'Expenses',
        ]);

        // 7000 - Operating Expenses (Parent)
        $operatingExpenses = $this->createAccount([
            'code' => '7000',
            'name' => 'Operating Expenses',
            'account_type_id' => $expenseType->id,
            'category' => 'Operating Expenses',
            'preset' => AccountPreset::EXPENSES->value,
            'report_section' => 'Expenses',
            'is_system_account' => true,
            'allows_manual_entries' => false,
        ]);

        // 7100 - Payroll Expenses
        $payrollExpenses = $this->createAccount([
            'code' => '7100',
            'name' => 'Payroll Expenses',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'category' => 'Operating Expenses',
            'preset' => AccountPreset::PAYROLL_EXPENSES->value,
            'report_section' => 'Expenses',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '7101',
            'name' => 'Salaries Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $payrollExpenses->id,
            'preset' => AccountPreset::SALARIES_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7102',
            'name' => 'Wages Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $payrollExpenses->id,
            'preset' => AccountPreset::WAGES_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7103',
            'name' => 'Bonus Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $payrollExpenses->id,
            'preset' => AccountPreset::BONUS_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7104',
            'name' => 'Commission Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $payrollExpenses->id,
            'preset' => AccountPreset::COMMISSION_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        // 7200 - Occupancy Expenses
        $occupancyExpenses = $this->createAccount([
            'code' => '7200',
            'name' => 'Occupancy Expenses',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '7201',
            'name' => 'Rent Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $occupancyExpenses->id,
            'preset' => AccountPreset::RENT_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7202',
            'name' => 'Utilities Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $occupancyExpenses->id,
            'preset' => AccountPreset::UTILITIES_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7203',
            'name' => 'Repairs and Maintenance',
            'account_type_id' => $expenseType->id,
            'parent_id' => $occupancyExpenses->id,
            'preset' => AccountPreset::REPAIRS_AND_MAINTENANCE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        // 7300 - Office Expenses
        $officeExpenses = $this->createAccount([
            'code' => '7300',
            'name' => 'Office Expenses',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '7301',
            'name' => 'Office Supplies Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $officeExpenses->id,
            'preset' => AccountPreset::OFFICE_SUPPLIES_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7302',
            'name' => 'Postage Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $officeExpenses->id,
            'preset' => AccountPreset::POSTAGE_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7303',
            'name' => 'Printing Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $officeExpenses->id,
            'preset' => AccountPreset::PRINTING_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7305',
            'name' => 'Telephone Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $officeExpenses->id,
            'preset' => AccountPreset::TELEPHONE_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7306',
            'name' => 'Internet Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $officeExpenses->id,
            'preset' => AccountPreset::INTERNET_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        // 7400 - Marketing & Sales Expenses
        $marketingExpenses = $this->createAccount([
            'code' => '7400',
            'name' => 'Marketing & Sales Expenses',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '7401',
            'name' => 'Advertising Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $marketingExpenses->id,
            'preset' => AccountPreset::ADVERTISING_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7402',
            'name' => 'Marketing Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $marketingExpenses->id,
            'preset' => AccountPreset::MARKETING_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        // 7500 - Professional Fees
        $professionalFees = $this->createAccount([
            'code' => '7500',
            'name' => 'Professional Fees',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::PROFESSIONAL_FEES->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7501',
            'name' => 'Legal Fees',
            'account_type_id' => $expenseType->id,
            'parent_id' => $professionalFees->id,
            'preset' => AccountPreset::LEGAL_FEES->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7502',
            'name' => 'Accounting Fees',
            'account_type_id' => $expenseType->id,
            'parent_id' => $professionalFees->id,
            'preset' => AccountPreset::ACCOUNTING_FEES->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7503',
            'name' => 'Consulting Fees',
            'account_type_id' => $expenseType->id,
            'parent_id' => $professionalFees->id,
            'preset' => AccountPreset::CONSULTING_FEES->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        // 7600 - Travel & Entertainment
        $this->createAccount([
            'code' => '7600',
            'name' => 'Travel Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::TRAVEL_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7601',
            'name' => 'Meals and Entertainment',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::MEALS_AND_ENTERTAINMENT->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7602',
            'name' => 'Vehicle Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::VEHICLE_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7603',
            'name' => 'Shipping Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::SHIPPING_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        // 7700 - Insurance & Depreciation
        $this->createAccount([
            'code' => '7700',
            'name' => 'Insurance Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::INSURANCE_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7800',
            'name' => 'Depreciation Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::DEPRECIATION_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '7801',
            'name' => 'Amortization Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::AMORTIZATION_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        // 8000 - Financial Expenses
        $financialExpenses = $this->createAccount([
            'code' => '8000',
            'name' => 'Financial Expenses',
            'account_type_id' => $expenseType->id,
            'category' => 'Non-Operating Expenses',
            'report_section' => 'Expenses',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '8001',
            'name' => 'Interest Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $financialExpenses->id,
            'preset' => AccountPreset::INTEREST_EXPENSE->value,
            'category' => 'Non-Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '8002',
            'name' => 'Bank Charges',
            'account_type_id' => $expenseType->id,
            'parent_id' => $financialExpenses->id,
            'preset' => AccountPreset::BANK_CHARGES->value,
            'category' => 'Non-Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '8004',
            'name' => 'Loss on Sale of Assets',
            'account_type_id' => $expenseType->id,
            'parent_id' => $financialExpenses->id,
            'preset' => AccountPreset::LOSS_ON_SALE_OF_ASSETS->value,
            'category' => 'Non-Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '8003',
            'name' => 'Foreign Exchange Loss',
            'account_type_id' => $expenseType->id,
            'parent_id' => $financialExpenses->id,
            'preset' => AccountPreset::FOREIGN_EXCHANGE_LOSS->value,
            'category' => 'Non-Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '8005',
            'name' => 'Credit Card Fees',
            'account_type_id' => $expenseType->id,
            'parent_id' => $financialExpenses->id,
            'preset' => AccountPreset::CREDIT_CARD_FEES->value,
            'category' => 'Non-Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '8006',
            'name' => 'Merchant Services Fees',
            'account_type_id' => $expenseType->id,
            'parent_id' => $financialExpenses->id,
            'preset' => AccountPreset::MERCHANT_SERVICES_FEES->value,
            'category' => 'Non-Operating Expenses',
            'report_section' => 'Expenses',
        ]);

        // 8100 - Tax Expenses
        $this->createAccount([
            'code' => '8100',
            'name' => 'Income Tax Expense',
            'account_type_id' => $expenseType->id,
            'preset' => AccountPreset::INCOME_TAX_EXPENSE->value,
            'category' => 'Tax Expenses',
            'report_section' => 'Expenses',
        ]);

        // 8200 - Other Expenses
        $otherExpenses = $this->createAccount([
            'code' => '8200',
            'name' => 'Other Expenses',
            'account_type_id' => $expenseType->id,
            'category' => 'Other Expenses',
            'report_section' => 'Expenses',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '8201',
            'name' => 'Bad Debt Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $otherExpenses->id,
            'preset' => AccountPreset::BAD_DEBT_EXPENSE->value,
            'category' => 'Other Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '8202',
            'name' => 'Penalties and Fines',
            'account_type_id' => $expenseType->id,
            'parent_id' => $otherExpenses->id,
            'preset' => AccountPreset::PENALTIES_AND_FINES->value,
            'category' => 'Other Expenses',
            'report_section' => 'Expenses',
        ]);

        $this->createAccount([
            'code' => '8203',
            'name' => 'Donations',
            'account_type_id' => $expenseType->id,
            'parent_id' => $otherExpenses->id,
            'preset' => AccountPreset::DONATIONS->value,
            'category' => 'Other Expenses',
            'report_section' => 'Expenses',
        ]);
    }

    /**
     * Create an account with the given attributes
     */
    protected function createAccount(array $attributes): Account
    {
        return Account::updateOrCreate(
            ['code' => $attributes['code']],
            array_merge([
                'is_active' => true,
                'is_system_account' => false,
                'allows_manual_entries' => true,
                'currency_code' => config('laravel_accounting.default_currency_code', 'USD'),
            ], $attributes)
        );
    }
}
