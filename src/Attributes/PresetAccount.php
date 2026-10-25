<?php

namespace RingleSoft\LaravelAccounting\Attributes;

use Attribute;
use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum;
use RingleSoft\LaravelAccounting\Enums\NormalBalance;

/**
 * Describes the account to seed for a preset enum case. The package's own
 * chart of accounts is defined this way on Enums\AccountPreset, and custom
 * preset enums use it the same way. Cases without it are still valid
 * presets, they just aren't seeded.
 *
 *     enum MyPreset: string implements AccountPresetContract
 *     {
 *         #[PresetAccount(name: 'Secondary Bank', type: AccountTypeEnum::ASSET,
 *             parent: AccountPreset::CASH_AND_CASH_EQUIVALENT)]
 *         case SECONDARY_BANK = 'SECONDARY_BANK';
 *     }
 *
 * $parent may be a built-in or a custom preset, and is required for custom
 * presets: only the package's chart defines root (first-level) accounts.
 * $code is generated under the parent when omitted (see
 * Support\AccountCodeGenerator). $category and $reportSection default to the
 * parent's, and $normalBalance to the account type's. $isSystemAccount is
 * reserved for the package's own chart.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class PresetAccount
{
    public function __construct(
        public readonly string $name,
        public readonly AccountTypeEnum $type,
        public readonly ?AccountPresetContract $parent = null,
        public readonly ?string $code = null,
        public readonly ?NormalBalance $normalBalance = null,
        public readonly ?string $category = null,
        public readonly ?string $subCategory = null,
        public readonly ?string $reportSection = null,
        public readonly bool $allowsManualEntries = true,
        public readonly bool $isSystemAccount = false,
        public readonly ?string $description = null,
    ) {}

    /**
     * A copy with some fields replaced (used for config overrides).
     *
     * @param  array{name?: string, code?: string, description?: string}  $overrides
     */
    public function with(array $overrides): self
    {
        return new self(
            name: $overrides['name'] ?? $this->name,
            type: $this->type,
            parent: $this->parent,
            code: $overrides['code'] ?? $this->code,
            normalBalance: $this->normalBalance,
            category: $this->category,
            subCategory: $this->subCategory,
            reportSection: $this->reportSection,
            allowsManualEntries: $this->allowsManualEntries,
            isSystemAccount: $this->isSystemAccount,
            description: $overrides['description'] ?? $this->description,
        );
    }
}
