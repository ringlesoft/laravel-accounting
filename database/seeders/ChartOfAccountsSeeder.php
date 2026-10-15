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
     * with hierarchical structure and globally accepted numbering:
     * - 10000-19999: Assets
     * - 20000-29999: Liabilities
     * - 30000-39999: Equity
     * - 40000-49999: Revenue/Income
     * - 50000-99999: Expenses
     */

    /**
     * @var string[]
     */
    private $presets = [];

    public function run(string $industry = 'full'): void
    {
        // Ensure account types exist
        $accountTypes = $this->getAccountTypes();
        $this->presets = collect(AccountPreset::byIndustry()[$industry])->pluck('value')->toArray();

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
     * Seed Asset Accounts (10000-19999)
     */
    protected function seedAssets(array $accountTypes): void
    {
        $assetType = $accountTypes['Asset'];

        // 10000 - Current Assets (Parent)
        $currentAssets = $this->createAccount([
            'code' => '10000',
            'name' => 'Current Assets',
            'account_type_id' => $assetType->id,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
            'is_system_account' => true,
            'allows_manual_entries' => false,
        ]);

        // 10100 - Cash and Cash Equivalents
        $cashParent = $this->createAccount([
            'code' => '10100',
            'name' => 'Cash and Cash Equivalents',
            'account_type_id' => $assetType->id,
            'parent_id' => $currentAssets->id,
            'preset' => AccountPreset::CASH_AND_CASH_EQUIVALENT->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '10101',
            'name' => 'Cash',
            'account_type_id' => $assetType->id,
            'parent_id' => $cashParent->id,
            'preset' => AccountPreset::CASH->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10102',
            'name' => 'Petty Cash',
            'account_type_id' => $assetType->id,
            'parent_id' => $cashParent->id,
            'preset' => AccountPreset::PETTY_CASH->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10103',
            'name' => 'Cash in Bank',
            'account_type_id' => $assetType->id,
            'parent_id' => $cashParent->id,
            'preset' => AccountPreset::CASH_IN_BANK->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10104',
            'name' => 'Undeposited Funds',
            'account_type_id' => $assetType->id,
            'parent_id' => $cashParent->id,
            'preset' => AccountPreset::UNDEPOSITED_FUNDS->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        // 10200 - Accounts Receivable
        $receivablesParent = $this->createAccount([
            'code' => '10200',
            'name' => 'Accounts Receivable',
            'account_type_id' => $assetType->id,
            'parent_id' => $currentAssets->id,
            'preset' => AccountPreset::ACCOUNTS_RECEIVABLE->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10201',
            'name' => 'Trade Receivables',
            'account_type_id' => $assetType->id,
            'parent_id' => $receivablesParent->id,
            'preset' => AccountPreset::TRADE_RECEIVABLES->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10202',
            'name' => 'Notes Receivable',
            'account_type_id' => $assetType->id,
            'parent_id' => $receivablesParent->id,
            'preset' => AccountPreset::NOTES_RECEIVABLE->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10203',
            'name' => 'Other Receivables',
            'account_type_id' => $assetType->id,
            'parent_id' => $receivablesParent->id,
            'preset' => AccountPreset::OTHER_RECEIVABLES->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10204',
            'name' => 'Accrued Revenue',
            'account_type_id' => $assetType->id,
            'parent_id' => $receivablesParent->id,
            'preset' => AccountPreset::ACCRUED_REVENUE->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10210',
            'name' => 'Allowance for Doubtful Accounts',
            'account_type_id' => $assetType->id,
            'parent_id' => $receivablesParent->id,
            'preset' => AccountPreset::ALLOWANCE_FOR_DOUBTFUL_ACCOUNTS->value,
            'category' => 'Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
            'normal_balance' => 'CREDIT',
        ]);

        // 10300 - Inventory
        $inventoryParent = $this->createAccount([
            'code' => '10300',
            'name' => 'Inventory',
            'account_type_id' => $assetType->id,
            'parent_id' => $currentAssets->id,
            'preset' => AccountPreset::INVENTORY->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '10301',
            'name' => 'Raw Materials',
            'account_type_id' => $assetType->id,
            'parent_id' => $inventoryParent->id,
            'preset' => AccountPreset::RAW_MATERIALS->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10302',
            'name' => 'Work in Progress',
            'account_type_id' => $assetType->id,
            'parent_id' => $inventoryParent->id,
            'preset' => AccountPreset::WORK_IN_PROGRESS->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10303',
            'name' => 'Finished Goods',
            'account_type_id' => $assetType->id,
            'parent_id' => $inventoryParent->id,
            'preset' => AccountPreset::FINISHED_GOODS->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10304',
            'name' => 'Merchandise Inventory',
            'account_type_id' => $assetType->id,
            'parent_id' => $inventoryParent->id,
            'preset' => AccountPreset::MERCHANDISE_INVENTORY->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        // 10400 - Prepaid Expenses
        $prepaidParent = $this->createAccount([
            'code' => '10400',
            'name' => 'Prepaid Expenses',
            'account_type_id' => $assetType->id,
            'parent_id' => $currentAssets->id,
            'preset' => AccountPreset::PREPAID_EXPENSES->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10401',
            'name' => 'Prepaid Insurance',
            'account_type_id' => $assetType->id,
            'parent_id' => $prepaidParent->id,
            'preset' => AccountPreset::PREPAID_INSURANCE->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10402',
            'name' => 'Prepaid Rent',
            'account_type_id' => $assetType->id,
            'parent_id' => $prepaidParent->id,
            'preset' => AccountPreset::PREPAID_RENT->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10403',
            'name' => 'Prepaid Subscriptions',
            'account_type_id' => $assetType->id,
            'parent_id' => $prepaidParent->id,
            'preset' => AccountPreset::PREPAID_SUBSCRIPTIONS->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10405',
            'name' => 'Advance to Suppliers',
            'account_type_id' => $assetType->id,
            'parent_id' => $prepaidParent->id,
            'preset' => AccountPreset::ADVANCE_TO_SUPPLIERS->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '10406',
            'name' => 'Advance to Employees',
            'account_type_id' => $assetType->id,
            'parent_id' => $prepaidParent->id,
            'preset' => AccountPreset::ADVANCE_TO_EMPLOYEES->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        // 10500 - Tax Assets
        $this->createAccount([
            'code' => '10500',
            'name' => 'Input VAT',
            'account_type_id' => $assetType->id,
            'parent_id' => $currentAssets->id,
            'preset' => AccountPreset::INPUT_VAT->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        // 10600 - Short-term Investments
        $this->createAccount([
            'code' => '10600',
            'name' => 'Short-term Investments',
            'account_type_id' => $assetType->id,
            'parent_id' => $currentAssets->id,
            'preset' => AccountPreset::SHORT_TERM_INVESTMENTS->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        // 10700 - Digital Assets
        $this->createAccount([
            'code' => '10700',
            'name' => 'Digital Assets',
            'account_type_id' => $assetType->id,
            'parent_id' => $currentAssets->id,
            'preset' => AccountPreset::DIGITAL_ASSETS->value,
            'category' => 'Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        // 15000 - Non-Current Assets (Parent)
        $nonCurrentAssets = $this->createAccount([
            'code' => '15000',
            'name' => 'Non-Current Assets',
            'account_type_id' => $assetType->id,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
            'is_system_account' => true,
            'allows_manual_entries' => false,
        ]);

        // 15100 - Property, Plant & Equipment
        $ppeParent = $this->createAccount([
            'code' => '15100',
            'name' => 'Property, Plant & Equipment',
            'account_type_id' => $assetType->id,
            'parent_id' => $nonCurrentAssets->id,
            'category' => 'Non-Current Assets',
            'preset' => AccountPreset::FIXED_ASSETS->value,
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '15101',
            'name' => 'Land',
            'account_type_id' => $assetType->id,
            'parent_id' => $ppeParent->id,
            'preset' => AccountPreset::LAND->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '15102',
            'name' => 'Buildings',
            'account_type_id' => $assetType->id,
            'parent_id' => $ppeParent->id,
            'preset' => AccountPreset::BUILDINGS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '15103',
            'name' => 'Machinery and Equipment',
            'account_type_id' => $assetType->id,
            'parent_id' => $ppeParent->id,
            'preset' => AccountPreset::MACHINERY_AND_EQUIPMENT->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '15104',
            'name' => 'Office Equipment',
            'account_type_id' => $assetType->id,
            'parent_id' => $ppeParent->id,
            'preset' => AccountPreset::OFFICE_EQUIPMENT->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '15105',
            'name' => 'Furniture and Fixtures',
            'account_type_id' => $assetType->id,
            'parent_id' => $ppeParent->id,
            'preset' => AccountPreset::FURNITURE_AND_FIXTURES->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '15106',
            'name' => 'Vehicles',
            'account_type_id' => $assetType->id,
            'parent_id' => $ppeParent->id,
            'preset' => AccountPreset::VEHICLES->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '15107',
            'name' => 'IT Equipment',
            'account_type_id' => $assetType->id,
            'parent_id' => $ppeParent->id,
            'preset' => AccountPreset::COMPUTER_EQUIPMENT->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '15108',
            'name' => 'Leasehold Improvements',
            'account_type_id' => $assetType->id,
            'parent_id' => $ppeParent->id,
            'preset' => AccountPreset::LEASEHOLD_IMPROVEMENTS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '15109',
            'name' => 'Construction in Progress',
            'account_type_id' => $assetType->id,
            'parent_id' => $ppeParent->id,
            'preset' => AccountPreset::CONSTRUCTION_IN_PROGRESS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        // 15200 - Accumulated Depreciation (Contra Asset)
        $accumDepreciation = $this->createAccount([
            'code' => '15200',
            'name' => 'Accumulated Depreciation',
            'account_type_id' => $assetType->id,
            'parent_id' => $nonCurrentAssets->id,
            'preset' => AccountPreset::ACCUMULATED_DEPRECIATION->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '15201',
            'name' => 'Accumulated Depreciation - Buildings',
            'account_type_id' => $assetType->id,
            'parent_id' => $accumDepreciation->id,
            'preset' => AccountPreset::ACCUM_DEPR_BUILDINGS->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '15202',
            'name' => 'Accumulated Depreciation - Equipment',
            'account_type_id' => $assetType->id,
            'parent_id' => $accumDepreciation->id,
            'preset' => AccountPreset::ACCUM_DEPR_EQUIPMENT->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '15203',
            'name' => 'Accumulated Depreciation - Vehicles',
            'account_type_id' => $assetType->id,
            'parent_id' => $accumDepreciation->id,
            'preset' => AccountPreset::ACCUM_DEPR_VEHICLES->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '15204',
            'name' => 'Accumulated Depreciation - Furniture',
            'account_type_id' => $assetType->id,
            'parent_id' => $accumDepreciation->id,
            'preset' => AccountPreset::ACCUM_DEPR_FURNITURE->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '15205',
            'name' => 'Accumulated Depreciation - Office Equipment',
            'account_type_id' => $assetType->id,
            'parent_id' => $accumDepreciation->id,
            'preset' => AccountPreset::ACCUM_DEPR_OFFICE_EQUIPMENT->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '15206',
            'name' => 'Accumulated Depreciation - IT Equipment',
            'account_type_id' => $assetType->id,
            'parent_id' => $accumDepreciation->id,
            'preset' => AccountPreset::ACCUM_DEPR_IT_EQUIPMENT->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '15207',
            'name' => 'Accumulated Depreciation - Leasehold Improvements',
            'account_type_id' => $assetType->id,
            'parent_id' => $accumDepreciation->id,
            'preset' => AccountPreset::ACCUM_DEPR_LEASEHOLD->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
            'normal_balance' => 'CREDIT',
        ]);

        // 15300 - Intangible Assets
        $intangibleParent = $this->createAccount([
            'code' => '15300',
            'name' => 'Intangible Assets',
            'account_type_id' => $assetType->id,
            'parent_id' => $nonCurrentAssets->id,
            'preset' => AccountPreset::INTANGIBLE_ASSETS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '15301',
            'name' => 'Goodwill',
            'account_type_id' => $assetType->id,
            'parent_id' => $intangibleParent->id,
            'preset' => AccountPreset::GOODWILL->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '15302',
            'name' => 'Patents',
            'account_type_id' => $assetType->id,
            'parent_id' => $intangibleParent->id,
            'preset' => AccountPreset::PATENTS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '15303',
            'name' => 'Software',
            'account_type_id' => $assetType->id,
            'parent_id' => $intangibleParent->id,
            'preset' => AccountPreset::SOFTWARE->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '15304',
            'name' => 'Trademarks',
            'account_type_id' => $assetType->id,
            'parent_id' => $intangibleParent->id,
            'preset' => AccountPreset::TRADEMARKS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '15305',
            'name' => 'Copyrights',
            'account_type_id' => $assetType->id,
            'parent_id' => $intangibleParent->id,
            'preset' => AccountPreset::COPYRIGHTS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        $accumAmortization = $this->createAccount([
            'code' => '15310',
            'name' => 'Accumulated Amortization',
            'account_type_id' => $assetType->id,
            'parent_id' => $intangibleParent->id,
            'preset' => AccountPreset::ACCUMULATED_AMORTIZATION->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '15311',
            'name' => 'Accumulated Amortization - Patents',
            'account_type_id' => $assetType->id,
            'parent_id' => $accumAmortization->id,
            'preset' => AccountPreset::ACCUM_AMORT_PATENTS->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '15312',
            'name' => 'Accumulated Amortization - Software',
            'account_type_id' => $assetType->id,
            'parent_id' => $accumAmortization->id,
            'preset' => AccountPreset::ACCUM_AMORT_SOFTWARE->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '15313',
            'name' => 'Accumulated Amortization - Trademarks',
            'account_type_id' => $assetType->id,
            'parent_id' => $accumAmortization->id,
            'preset' => AccountPreset::ACCUM_AMORT_TRADEMARKS->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '15314',
            'name' => 'Accumulated Amortization - Copyrights',
            'account_type_id' => $assetType->id,
            'parent_id' => $accumAmortization->id,
            'preset' => AccountPreset::ACCUM_AMORT_COPYRIGHTS->value,
            'category' => 'Non-Current Assets',
            'sub_category' => 'Contra Asset',
            'report_section' => 'Assets',
            'normal_balance' => 'CREDIT',
        ]);

        // 15400 - Long-term Investments
        $this->createAccount([
            'code' => '15400',
            'name' => 'Long-term Investments',
            'account_type_id' => $assetType->id,
            'parent_id' => $nonCurrentAssets->id,
            'preset' => AccountPreset::LONG_TERM_INVESTMENTS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        // 15450 - Security Deposits (Non-Current)
        $this->createAccount([
            'code' => '15450',
            'name' => 'Security Deposits',
            'account_type_id' => $assetType->id,
            'parent_id' => $nonCurrentAssets->id,
            'preset' => AccountPreset::SECURITY_DEPOSITS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        // 15500 - Right-of-Use Assets (IFRS 16/ASC 842)
        $this->createAccount([
            'code' => '15500',
            'name' => 'Right-of-Use Assets',
            'account_type_id' => $assetType->id,
            'parent_id' => $nonCurrentAssets->id,
            'preset' => AccountPreset::RIGHT_OF_USE_ASSETS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);

        // 15600 - Deferred Tax Asset
        $this->createAccount([
            'code' => '15600',
            'name' => 'Deferred Tax Asset',
            'account_type_id' => $assetType->id,
            'parent_id' => $nonCurrentAssets->id,
            'preset' => AccountPreset::DEFERRED_TAX_ASSETS->value,
            'category' => 'Non-Current Assets',
            'report_section' => 'Assets',
            'normal_balance' => 'DEBIT',
        ]);
    }

    /**
     * Seed Liability Accounts (20000-29999)
     */
    protected function seedLiabilities(array $accountTypes): void
    {
        $liabilityType = $accountTypes['Liability'];

        // 20000 - Current Liabilities (Parent)
        $currentLiabilities = $this->createAccount([
            'code' => '20000',
            'name' => 'Current Liabilities',
            'account_type_id' => $liabilityType->id,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
            'is_system_account' => true,
            'allows_manual_entries' => false,
        ]);

        // 20100 - Accounts Payable
        $payablesParent = $this->createAccount([
            'code' => '20100',
            'name' => 'Accounts Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::ACCOUNTS_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20101',
            'name' => 'Trade Payables',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payablesParent->id,
            'preset' => AccountPreset::TRADE_PAYABLES->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20102',
            'name' => 'Notes Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payablesParent->id,
            'preset' => AccountPreset::NOTES_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        // 20105 - Dividends Payable
        $this->createAccount([
            'code' => '20105',
            'name' => 'Dividends Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::DIVIDENDS_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        // 20200 - Accrued Expenses
        $accruedParent = $this->createAccount([
            'code' => '20200',
            'name' => 'Accrued Expenses',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::ACCRUED_EXPENSES->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20201',
            'name' => 'Accrued Salaries',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $accruedParent->id,
            'preset' => AccountPreset::ACCRUED_SALARIES->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20202',
            'name' => 'Accrued Interest',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $accruedParent->id,
            'preset' => AccountPreset::ACCRUED_INTEREST->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20203',
            'name' => 'Lease Liability (Current)',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $accruedParent->id,
            'preset' => AccountPreset::LEASE_LIABILITY_CURRENT->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20204',
            'name' => 'Accrued Utilities',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $accruedParent->id,
            'preset' => AccountPreset::ACCRUED_UTILITIES->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        // 20300 - Short-term Debt
        $this->createAccount([
            'code' => '20300',
            'name' => 'Short-term Loans',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::SHORT_TERM_LOANS->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20301',
            'name' => 'Current Portion of Long-term Debt',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::CURRENT_PORTION_OF_LONG_TERM_DEBT->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20302',
            'name' => 'Line of Credit',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::LINE_OF_CREDIT->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        // 3300 - Unearned Revenue
        $this->createAccount([
            'code' => '20400',
            'name' => 'Unearned Revenue',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::UNEARNED_REVENUE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20401',
            'name' => 'Customer Deposits',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::CUSTOMER_DEPOSITS->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20402',
            'name' => 'Deferred Revenue',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::DEFERRED_REVENUE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20403',
            'name' => 'Advances from Customers',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'preset' => AccountPreset::ADVANCES_FROM_CUSTOMERS->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        // 3400 - Tax Liabilities
        $taxParent = $this->createAccount([
            'code' => '20500',
            'name' => 'Tax Liabilities',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '20501',
            'name' => 'Sales Tax Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $taxParent->id,
            'preset' => AccountPreset::SALES_TAX_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20502',
            'name' => 'VAT Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $taxParent->id,
            'preset' => AccountPreset::VAT_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20503',
            'name' => 'Income Tax Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $taxParent->id,
            'preset' => AccountPreset::INCOME_TAX_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20504',
            'name' => 'GST Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $taxParent->id,
            'preset' => AccountPreset::GST_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20505',
            'name' => 'Payroll Tax Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $taxParent->id,
            'preset' => AccountPreset::PAYROLL_TAX_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        // 3500 - Payroll Liabilities
        $payrollParent = $this->createAccount([
            'code' => '20600',
            'name' => 'Payroll Liabilities',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $currentLiabilities->id,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '20601',
            'name' => 'Wages Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::WAGES_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20602',
            'name' => 'Salaries Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::SALARIES_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20603',
            'name' => 'Bonuses Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::BONUSES_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20606',
            'name' => 'Vacation Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::VACATION_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20610',
            'name' => 'Payroll Clearing',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::PAYROLL_CLEARING->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20611',
            'name' => 'Federal Withholding Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::FEDERAL_WITHHOLDING_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20612',
            'name' => 'Social Security Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::SOCIAL_SECURITY_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '20613',
            'name' => 'Medicare Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $payrollParent->id,
            'preset' => AccountPreset::MEDICARE_PAYABLE->value,
            'category' => 'Current Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        // 3600 - Long-term Liabilities (Parent)
        $longTermLiabilities = $this->createAccount([
            'code' => '25000',
            'name' => 'Long-term Liabilities',
            'account_type_id' => $liabilityType->id,
            'category' => 'Long-term Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
            'is_system_account' => true,
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '25001',
            'name' => 'Long-term Debt',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $longTermLiabilities->id,
            'preset' => AccountPreset::LONG_TERM_DEBT->value,
            'category' => 'Long-term Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '25002',
            'name' => 'Bonds Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $longTermLiabilities->id,
            'preset' => AccountPreset::BONDS_PAYABLE->value,
            'category' => 'Long-term Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '25003',
            'name' => 'Mortgage Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $longTermLiabilities->id,
            'preset' => AccountPreset::MORTGAGE_PAYABLE->value,
            'category' => 'Long-term Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '25004',
            'name' => 'Deferred Tax Liability',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $longTermLiabilities->id,
            'preset' => AccountPreset::DEFERRED_TAX_LIABILITIES->value,
            'category' => 'Long-term Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '25005',
            'name' => 'Lease Liability (Non-Current)',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $longTermLiabilities->id,
            'preset' => AccountPreset::LEASE_LIABILITIES->value,
            'category' => 'Long-term Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '25006',
            'name' => 'Loan Payable',
            'account_type_id' => $liabilityType->id,
            'parent_id' => $longTermLiabilities->id,
            'preset' => AccountPreset::LOAN_PAYABLE->value,
            'category' => 'Long-term Liabilities',
            'report_section' => 'Liabilities',
            'normal_balance' => 'CREDIT',
        ]);
    }

    /**
     * Seed Equity Accounts (30000-39999)
     */
    protected function seedEquity(array $accountTypes): void
    {
        $equityType = $accountTypes['Equity'];

        // 4000 - Equity (Parent)
        $equity = $this->createAccount([
            'code' => '30000',
            'name' => 'Equity',
            'account_type_id' => $equityType->id,
            'category' => 'Equity',
            'report_section' => 'Equity',
            'normal_balance' => 'CREDIT',
            'is_system_account' => true,
            'allows_manual_entries' => false,
        ]);

        // 30050 - Opening Balance Equity (CRITICAL for software deployment)
        $this->createAccount([
            'code' => '30050',
            'name' => 'Opening Balance Equity',
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::OPENING_BALANCE_EQUITY->value,
            'category' => 'Equity',
            'report_section' => 'Equity',
            'normal_balance' => 'CREDIT',
            'is_system_account' => true,
        ]);

        // 4010 - Share Capital
        $this->createAccount([
            'code' => '30100',
            'name' => 'Common Stock',
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::COMMON_STOCK->value,
            'category' => 'Equity',
            'report_section' => 'Equity',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '30200',
            'name' => 'Additional Paid-in Capital',
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::ADDITIONAL_PAID_IN_CAPITAL->value,
            'category' => 'Equity',
            'report_section' => 'Equity',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '30300',
            'name' => 'Preferred Stock',
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::PREFERRED_STOCK->value,
            'category' => 'Equity',
            'report_section' => 'Equity',
            'normal_balance' => 'CREDIT',
        ]);

        // 4100 - Retained Earnings
        $this->createAccount([
            'code' => '30400',
            'name' => 'Retained Earnings',
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::RETAINED_EARNINGS->value,
            'category' => 'Equity',
            'report_section' => 'Equity',
            'normal_balance' => 'CREDIT',
            'is_system_account' => true,
        ]);

        $this->createAccount([
            'code' => '30410',
            'name' => 'Current Year Earnings',
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::CURRENT_YEAR_EARNINGS->value,
            'category' => 'Equity',
            'report_section' => 'Equity',
            'normal_balance' => 'CREDIT',
            'is_system_account' => true,
        ]);

        // 4200 - Owner's Equity (for sole proprietorships/partnerships)
        $this->createAccount([
            'code' => '30500',
            'name' => "Owner's Equity",
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::OWNERS_EQUITY->value,
            'category' => 'Equity',
            'report_section' => 'Equity',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '30510',
            'name' => "Owner's Draw",
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::OWNERS_DRAW->value,
            'category' => 'Equity',
            'sub_category' => 'Contra Equity',
            'report_section' => 'Equity',
            'normal_balance' => 'DEBIT',
        ]);

        // 4300 - Dividends
        $this->createAccount([
            'code' => '30600',
            'name' => 'Dividends Declared',
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::DIVIDENDS_DECLARED->value,
            'category' => 'Equity',
            'sub_category' => 'Contra Equity',
            'report_section' => 'Equity',
            'normal_balance' => 'DEBIT',
        ]);

        // 30700 - Treasury Stock (Contra Equity)
        $this->createAccount([
            'code' => '30700',
            'name' => 'Treasury Stock',
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::TREASURY_STOCK->value,
            'category' => 'Equity',
            'sub_category' => 'Contra Equity',
            'report_section' => 'Equity',
            'normal_balance' => 'DEBIT',
        ]);

        // 30800 - Accumulated Other Comprehensive Income (AOCI)
        $this->createAccount([
            'code' => '30800',
            'name' => 'Accumulated Other Comprehensive Income',
            'account_type_id' => $equityType->id,
            'parent_id' => $equity->id,
            'preset' => AccountPreset::OTHER_COMPREHENSIVE_INCOME->value,
            'category' => 'Equity',
            'report_section' => 'Equity',
            'normal_balance' => 'CREDIT',
        ]);
    }

    /**
     * Seed Revenue Accounts (40000-49999)
     */
    protected function seedRevenue(array $accountTypes): void
    {
        $incomeType = $accountTypes['Income'];

        // 5000 - Revenue (Parent)
        $revenue = $this->createAccount([
            'code' => '40000',
            'name' => 'Revenue',
            'account_type_id' => $incomeType->id,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'CREDIT',
            'is_system_account' => true,
            'allows_manual_entries' => false,
        ]);

        // 5010 - Operating Revenue
        $operatingRevenue = $this->createAccount([
            'code' => '40100',
            'name' => 'Operating Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $revenue->id,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'CREDIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '40101',
            'name' => 'Sales Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $operatingRevenue->id,
            'preset' => AccountPreset::SALES_REVENUE->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '40102',
            'name' => 'Service Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $operatingRevenue->id,
            'preset' => AccountPreset::SERVICE_REVENUE->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '40103',
            'name' => 'Consulting Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $operatingRevenue->id,
            'preset' => AccountPreset::CONSULTING_REVENUE->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '40104',
            'name' => 'Rental Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $operatingRevenue->id,
            'preset' => AccountPreset::RENTAL_REVENUE->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '40106',
            'name' => 'Shipping Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $operatingRevenue->id,
            'preset' => AccountPreset::SHIPPING_REVENUE->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'CREDIT',
        ]);

        // 5100 - Other Revenue
        $otherRevenue = $this->createAccount([
            'code' => '40200',
            'name' => 'Other Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $revenue->id,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'CREDIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '40201',
            'name' => 'Interest Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $otherRevenue->id,
            'preset' => AccountPreset::INTEREST_REVENUE->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '40202',
            'name' => 'Dividend Revenue',
            'account_type_id' => $incomeType->id,
            'parent_id' => $otherRevenue->id,
            'preset' => AccountPreset::DIVIDEND_REVENUE->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '40203',
            'name' => 'Gain on Sale of Assets',
            'account_type_id' => $incomeType->id,
            'parent_id' => $otherRevenue->id,
            'preset' => AccountPreset::GAIN_ON_SALE_OF_ASSETS->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '40204',
            'name' => 'Foreign Exchange Gain',
            'account_type_id' => $incomeType->id,
            'parent_id' => $otherRevenue->id,
            'preset' => AccountPreset::FOREIGN_EXCHANGE_GAIN->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'CREDIT',
        ]);

        $this->createAccount([
            'code' => '40205',
            'name' => 'Other Income',
            'account_type_id' => $incomeType->id,
            'parent_id' => $otherRevenue->id,
            'preset' => AccountPreset::OTHER_INCOME->value,
            'category' => 'Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'CREDIT',
        ]);

        // 5900 - Contra Revenue
        $contraRevenue = $this->createAccount([
            'code' => '40900',
            'name' => 'Sales Returns and Allowances',
            'account_type_id' => $incomeType->id,
            'parent_id' => $revenue->id,
            'category' => 'Revenue',
            'sub_category' => 'Contra Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'DEBIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '40901',
            'name' => 'Sales Returns',
            'account_type_id' => $incomeType->id,
            'parent_id' => $contraRevenue->id,
            'preset' => AccountPreset::SALES_RETURNS->value,
            'category' => 'Revenue',
            'sub_category' => 'Contra Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '40902',
            'name' => 'Sales Discounts',
            'account_type_id' => $incomeType->id,
            'parent_id' => $contraRevenue->id,
            'preset' => AccountPreset::SALES_DISCOUNTS->value,
            'category' => 'Revenue',
            'sub_category' => 'Contra Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '40903',
            'name' => 'Sales Allowances',
            'account_type_id' => $incomeType->id,
            'parent_id' => $contraRevenue->id,
            'preset' => AccountPreset::SALES_ALLOWANCES->value,
            'category' => 'Revenue',
            'sub_category' => 'Contra Revenue',
            'report_section' => 'Income',
            'normal_balance' => 'DEBIT',
        ]);
    }

    /**
     * Seed Expense Accounts (50000-89999)
     */
    protected function seedExpenses(array $accountTypes): void
    {
        $expenseType = $accountTypes['Expense'];

        // 6000 - Cost of Goods Sold (Parent)
        $cogs = $this->createAccount([
            'code' => '50000',
            'name' => 'Cost of Goods Sold',
            'account_type_id' => $expenseType->id,
            'preset' => AccountPreset::COST_OF_GOODS_SOLD->value,
            'category' => 'Cost of Sales',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '50001',
            'name' => 'Cost of Sales',
            'account_type_id' => $expenseType->id,
            'parent_id' => $cogs->id,
            'preset' => AccountPreset::COST_OF_SALES->value,
            'category' => 'Cost of Sales',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '50002',
            'name' => 'Cost of Services',
            'account_type_id' => $expenseType->id,
            'parent_id' => $cogs->id,
            'preset' => AccountPreset::COST_OF_SERVICES->value,
            'category' => 'Cost of Sales',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '50003',
            'name' => 'Direct Labor',
            'account_type_id' => $expenseType->id,
            'parent_id' => $cogs->id,
            'preset' => AccountPreset::DIRECT_LABOR->value,
            'category' => 'Cost of Sales',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '50004',
            'name' => 'Direct Materials',
            'account_type_id' => $expenseType->id,
            'parent_id' => $cogs->id,
            'preset' => AccountPreset::DIRECT_MATERIALS->value,
            'category' => 'Cost of Sales',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '50005',
            'name' => 'Subcontractor Costs',
            'account_type_id' => $expenseType->id,
            'parent_id' => $cogs->id,
            'preset' => AccountPreset::SUBCONTRACTOR_COSTS->value,
            'category' => 'Cost of Sales',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '50006',
            'name' => 'Freight In',
            'account_type_id' => $expenseType->id,
            'parent_id' => $cogs->id,
            'preset' => AccountPreset::FREIGHT_IN->value,
            'category' => 'Cost of Sales',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '50007',
            'name' => 'Inventory Adjustments',
            'account_type_id' => $expenseType->id,
            'parent_id' => $cogs->id,
            'preset' => AccountPreset::INVENTORY_ADJUSTMENTS->value,
            'category' => 'Cost of Sales',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '50008',
            'name' => 'Manufacturing Overhead',
            'account_type_id' => $expenseType->id,
            'parent_id' => $cogs->id,
            'preset' => AccountPreset::MANUFACTURING_OVERHEAD->value,
            'category' => 'Cost of Sales',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        // 7000 - Operating Expenses (Parent)
        $operatingExpenses = $this->createAccount([
            'code' => '60000',
            'name' => 'Operating Expenses',
            'account_type_id' => $expenseType->id,
            'category' => 'Operating Expenses',
            'preset' => AccountPreset::OPERATING_EXPENSES->value,
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
            'is_system_account' => true,
            'allows_manual_entries' => false,
        ]);

        // 7100 - Payroll Expenses
        $payrollExpenses = $this->createAccount([
            'code' => '60100',
            'name' => 'Payroll Expenses',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'category' => 'Operating Expenses',
            'preset' => AccountPreset::PAYROLL_EXPENSES->value,
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '60101',
            'name' => 'Salaries Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $payrollExpenses->id,
            'preset' => AccountPreset::SALARIES_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60102',
            'name' => 'Wages Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $payrollExpenses->id,
            'preset' => AccountPreset::WAGES_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60103',
            'name' => 'Bonus Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $payrollExpenses->id,
            'preset' => AccountPreset::BONUS_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60104',
            'name' => 'Commission Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $payrollExpenses->id,
            'preset' => AccountPreset::COMMISSION_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60105',
            'name' => 'Payroll Taxes Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $payrollExpenses->id,
            'preset' => AccountPreset::PAYROLL_TAX_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60106',
            'name' => 'Employee Benefits Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $payrollExpenses->id,
            'preset' => AccountPreset::EMPLOYEE_BENEFITS_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60107',
            'name' => 'Training and Development',
            'account_type_id' => $expenseType->id,
            'parent_id' => $payrollExpenses->id,
            'preset' => AccountPreset::TRAINING_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        // 7200 - Occupancy Expenses
        $occupancyExpenses = $this->createAccount([
            'code' => '60200',
            'name' => 'Occupancy Expenses',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::OCCUPANCY_EXPENSES->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '60201',
            'name' => 'Rent Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $occupancyExpenses->id,
            'preset' => AccountPreset::RENT_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60202',
            'name' => 'Utilities Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $occupancyExpenses->id,
            'preset' => AccountPreset::UTILITIES_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60203',
            'name' => 'Repairs and Maintenance',
            'account_type_id' => $expenseType->id,
            'parent_id' => $occupancyExpenses->id,
            'preset' => AccountPreset::REPAIRS_AND_MAINTENANCE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        // 7300 - Office Expenses
        $officeExpenses = $this->createAccount([
            'code' => '60300',
            'name' => 'Office Expenses',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::OFFICE_EXPENSES->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '60301',
            'name' => 'Office Supplies Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $officeExpenses->id,
            'preset' => AccountPreset::OFFICE_SUPPLIES_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60302',
            'name' => 'Postage Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $officeExpenses->id,
            'preset' => AccountPreset::POSTAGE_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60303',
            'name' => 'Printing Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $officeExpenses->id,
            'preset' => AccountPreset::PRINTING_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60305',
            'name' => 'Telephone Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $officeExpenses->id,
            'preset' => AccountPreset::TELEPHONE_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60306',
            'name' => 'Internet Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $officeExpenses->id,
            'preset' => AccountPreset::INTERNET_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60307',
            'name' => 'Software & SaaS Subscription',
            'account_type_id' => $expenseType->id,
            'parent_id' => $officeExpenses->id,
            'preset' => AccountPreset::SUBSCRIPTION_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60308',
            'name' => 'Dues and Memberships',
            'account_type_id' => $expenseType->id,
            'parent_id' => $officeExpenses->id,
            'preset' => AccountPreset::DUES_AND_MEMBERSHIPS->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60309',
            'name' => 'Taxes and Licenses',
            'account_type_id' => $expenseType->id,
            'parent_id' => $officeExpenses->id,
            'preset' => AccountPreset::TAXES_AND_LICENSES->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        // 7400 - Marketing & Sales Expenses
        $marketingExpenses = $this->createAccount([
            'code' => '60400',
            'name' => 'Marketing & Sales Expenses',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::MARKETING_SALES_EXPENSES->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '60401',
            'name' => 'Advertising Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $marketingExpenses->id,
            'preset' => AccountPreset::ADVERTISING_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60402',
            'name' => 'Marketing Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $marketingExpenses->id,
            'preset' => AccountPreset::MARKETING_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        // 7500 - Professional Fees
        $professionalFees = $this->createAccount([
            'code' => '60500',
            'name' => 'Professional Fees',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::PROFESSIONAL_FEES->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60501',
            'name' => 'Legal Fees',
            'account_type_id' => $expenseType->id,
            'parent_id' => $professionalFees->id,
            'preset' => AccountPreset::LEGAL_FEES->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60502',
            'name' => 'Accounting Fees',
            'account_type_id' => $expenseType->id,
            'parent_id' => $professionalFees->id,
            'preset' => AccountPreset::ACCOUNTING_FEES->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60503',
            'name' => 'Consulting Fees',
            'account_type_id' => $expenseType->id,
            'parent_id' => $professionalFees->id,
            'preset' => AccountPreset::CONSULTING_FEES->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        // 7600 - Travel & Related
        $travelExpenses = $this->createAccount([
            'code' => '60600',
            'name' => 'Travel Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::TRAVEL_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '60601',
            'name' => 'Meals and Entertainment',
            'account_type_id' => $expenseType->id,
            'parent_id' => $travelExpenses->id,
            'preset' => AccountPreset::MEALS_AND_ENTERTAINMENT->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60602',
            'name' => 'Vehicle Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $travelExpenses->id,
            'preset' => AccountPreset::VEHICLE_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60603',
            'name' => 'Shipping Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $travelExpenses->id,
            'preset' => AccountPreset::SHIPPING_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        // 7700 - Insurance & Depreciation
        $this->createAccount([
            'code' => '60700',
            'name' => 'Insurance Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::INSURANCE_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60800',
            'name' => 'Depreciation Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::DEPRECIATION_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '60810',
            'name' => 'Amortization Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::AMORTIZATION_EXPENSE->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        // 60900 - Research & Development
        $this->createAccount([
            'code' => '60900',
            'name' => 'Research and Development',
            'account_type_id' => $expenseType->id,
            'parent_id' => $operatingExpenses->id,
            'preset' => AccountPreset::RESEARCH_AND_DEVELOPMENT->value,
            'category' => 'Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        // 8000 - Financial Expenses
        $financialExpenses = $this->createAccount([
            'code' => '70000',
            'name' => 'Financial Expenses',
            'account_type_id' => $expenseType->id,
            'preset' => AccountPreset::FINANCIAL_EXPENSES->value,
            'category' => 'Non-Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '70001',
            'name' => 'Interest Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $financialExpenses->id,
            'preset' => AccountPreset::INTEREST_EXPENSE->value,
            'category' => 'Non-Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '70002',
            'name' => 'Bank Charges',
            'account_type_id' => $expenseType->id,
            'parent_id' => $financialExpenses->id,
            'preset' => AccountPreset::BANK_CHARGES->value,
            'category' => 'Non-Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '70004',
            'name' => 'Loss on Sale of Assets',
            'account_type_id' => $expenseType->id,
            'parent_id' => $financialExpenses->id,
            'preset' => AccountPreset::LOSS_ON_SALE_OF_ASSETS->value,
            'category' => 'Non-Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '70003',
            'name' => 'Foreign Exchange Loss',
            'account_type_id' => $expenseType->id,
            'parent_id' => $financialExpenses->id,
            'preset' => AccountPreset::FOREIGN_EXCHANGE_LOSS->value,
            'category' => 'Non-Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '70005',
            'name' => 'Credit Card Fees',
            'account_type_id' => $expenseType->id,
            'parent_id' => $financialExpenses->id,
            'preset' => AccountPreset::CREDIT_CARD_FEES->value,
            'category' => 'Non-Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '70006',
            'name' => 'Merchant Services Fees',
            'account_type_id' => $expenseType->id,
            'parent_id' => $financialExpenses->id,
            'preset' => AccountPreset::MERCHANT_SERVICES_FEES->value,
            'category' => 'Non-Operating Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        // 8100 - Tax Expenses
        $this->createAccount([
            'code' => '70100',
            'name' => 'Income Tax Expense',
            'account_type_id' => $expenseType->id,
            'preset' => AccountPreset::INCOME_TAX_EXPENSE->value,
            'category' => 'Tax Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        // 8200 - Other Expenses
        $otherExpenses = $this->createAccount([
            'code' => '70200',
            'name' => 'Other Expenses',
            'account_type_id' => $expenseType->id,
            'preset' => AccountPreset::OTHER_EXPENSES->value,
            'category' => 'Other Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
            'allows_manual_entries' => false,
        ]);

        $this->createAccount([
            'code' => '70201',
            'name' => 'Bad Debt Expense',
            'account_type_id' => $expenseType->id,
            'parent_id' => $otherExpenses->id,
            'preset' => AccountPreset::BAD_DEBT_EXPENSE->value,
            'category' => 'Other Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '70204',
            'name' => 'Penalties and Fines',
            'account_type_id' => $expenseType->id,
            'parent_id' => $otherExpenses->id,
            'preset' => AccountPreset::PENALTIES_AND_FINES->value,
            'category' => 'Other Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);

        $this->createAccount([
            'code' => '70203',
            'name' => 'Donations',
            'account_type_id' => $expenseType->id,
            'parent_id' => $otherExpenses->id,
            'preset' => AccountPreset::DONATIONS->value,
            'category' => 'Other Expenses',
            'report_section' => 'Expenses',
            'normal_balance' => 'DEBIT',
        ]);
    }

    /**
     * Create an account with the given attributes
     */
    protected function createAccount(array $attributes): ?Account
    {
        if (($attributes['preset'] ?? null) && ! in_array($attributes['preset'], $this->presets, true)) {
            return null;
        }

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
