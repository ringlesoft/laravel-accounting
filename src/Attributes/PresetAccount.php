<?php

namespace RingleSoft\LaravelAccounting\Attributes;

use Attribute;
use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum;
use RingleSoft\LaravelAccounting\Enums\NormalBalance;

/**
 * Describes the account to seed for a custom preset enum case. Cases without
 * it are still valid presets, they just aren't seeded.
 *
 *     enum MyPreset: string implements AccountPresetContract
 *     {
 *         #[PresetAccount(code: '10105', name: 'Secondary Bank', type: AccountTypeEnum::ASSET,
 *             parent: AccountPreset::CASH_AND_CASH_EQUIVALENT)]
 *         case SECONDARY_BANK = 'SECONDARY_BANK';
 *     }
 *
 * $parent may be a built-in or a custom preset; its account must exist in the
 * same tenant (or be seeded in the same run). $category and $reportSection
 * default to the parent's, and $normalBalance to the account type's.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class PresetAccount
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly AccountTypeEnum $type,
        public readonly ?AccountPresetContract $parent = null,
        public readonly ?NormalBalance $normalBalance = null,
        public readonly ?string $category = null,
        public readonly ?string $reportSection = null,
        public readonly bool $allowsManualEntries = true,
        public readonly ?string $description = null,
    ) {}
}
