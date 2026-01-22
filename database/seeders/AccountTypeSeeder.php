<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum as AccountTypeEnum;
use RingleSoft\LaravelAccounting\Enums\NormalBalance;
use RingleSoft\LaravelAccounting\Models\AccountType;

class AccountTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the five fundamental account types with their normal balances
     * according to the accounting equation: Assets = Liabilities + Equity
     * and the double-entry bookkeeping system.
     */
    public function run(): void
    {
        $accountTypes = [
            [
                'code' => '1000',
                'name' => AccountTypeEnum::ASSET->value,
                'normal_balance' => NormalBalance::DEBIT->value,
                'report_section' => 'Assets',
                'description' => 'Resources owned by the entity that provide future economic benefits',
                'is_active' => true,
            ],
            [
                'code' => '3000',
                'name' => AccountTypeEnum::LIABILITY->value,
                'normal_balance' => NormalBalance::CREDIT->value,
                'report_section' => 'Liabilities',
                'description' => 'Obligations owed to external parties that will result in future outflows of resources',
                'is_active' => true,
            ],
            [
                'code' => '4000',
                'name' => AccountTypeEnum::EQUITY->value,
                'normal_balance' => NormalBalance::CREDIT->value,
                'report_section' => 'Equity',
                'description' => 'Residual interest in the assets after deducting liabilities (Owner\'s equity)',
                'is_active' => true,
            ],
            [
                'code' => '5000',
                'name' => AccountTypeEnum::INCOME->value,
                'normal_balance' => NormalBalance::CREDIT->value,
                'report_section' => 'Income',
                'description' => 'Revenue earned from business operations and other sources',
                'is_active' => true,
            ],
            [
                'code' => '6000',
                'name' => AccountTypeEnum::EXPENSE->value,
                'normal_balance' => NormalBalance::DEBIT->value,
                'report_section' => 'Expenses',
                'description' => 'Costs incurred in generating revenue and operating the business',
                'is_active' => true,
            ],
        ];

        foreach ($accountTypes as $type) {
            AccountType::firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
