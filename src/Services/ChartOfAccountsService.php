<?php

namespace RingleSoft\LaravelAccounting\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RingleSoft\LaravelAccounting\Attributes\PresetAccount;
use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\TenantResolver;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Enums\NormalBalance;
use RingleSoft\LaravelAccounting\Exceptions\InvalidPresetDefinitionException;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Scopes\MultiTenantScope;
use RingleSoft\LaravelAccounting\Support\AccountCodeGenerator;
use RingleSoft\LaravelAccounting\Support\ChartStructure;
use RingleSoft\LaravelAccounting\Support\PresetDefinitions;
use Throwable;

/**
 * Seeds the chart of accounts: the package's own, defined by #[PresetAccount]
 * on Enums\AccountPreset, together with the custom presets registered in
 * `laravel_accounting.custom_presets`.
 */
class ChartOfAccountsService
{
    /**
     * Fields of a built-in definition that `preset_overrides` may replace.
     */
    protected const OVERRIDABLE = ['name', 'code', 'description'];

    /**
     * The accounts to seed, keyed by preset value and ordered parents first.
     *
     * Built-in accounts are limited to the industry's presets and stripped of
     * `excluded_presets`; an excluded account's whole branch is left out.
     * Optional accounts are added only when listed in `included_presets`.
     * `preset_overrides` are applied. Root accounts can be neither excluded
     * nor overridden.
     *
     * `checkCode` marks the codes to check against the parent when seeding:
     * custom and overridden ones (the built-in chart's own codes fit by
     * construction).
     *
     * @return array<string, array{preset: AccountPresetContract, account: PresetAccount, builtIn: bool, checkCode: bool}>
     *
     * @throws InvalidPresetDefinitionException|InvalidArgumentException
     */
    public function definitions(string $industry = 'full', bool $includeBuiltIn = true, bool $includeCustom = true): array
    {
        $definitions = $includeBuiltIn ? $this->builtInDefinitions($industry) : [];

        if ($includeCustom) {
            $builtIn = PresetDefinitions::forEnum(AccountPreset::class);

            foreach (app(CustomPresetService::class)->definitions() as $value => $definition) {
                $parent = $definition['account']->parent;
                if ($includeBuiltIn && $parent !== null && isset($builtIn[$parent->value]) && ! isset($definitions[$parent->value])) {
                    throw new InvalidPresetDefinitionException(
                        PresetDefinitions::label($definition['preset']).'\'s parent '.PresetDefinitions::label($parent).
                        ' isn\'t part of this chart: it\'s excluded, or optional and not listed in included_presets.'
                    );
                }

                $definitions[$value] = $definition + ['builtIn' => false, 'checkCode' => true];
            }
        }

        $this->assertUniqueCodes($definitions);

        return $this->explicitCodesFirst(PresetDefinitions::orderParentsFirst($definitions));
    }

    /**
     * Move the definitions with an explicit code (and only explicit codes in
     * their ancestry) ahead of those whose code is generated, so a generated
     * code can never take one an explicitly-coded sibling still needs. The
     * parents-first order is kept within each group.
     *
     * @template T of array{preset: AccountPresetContract, account: PresetAccount, builtIn: bool, checkCode: bool}
     *
     * @param  array<string, T>  $definitions  Ordered parents first
     * @return array<string, T>
     */
    protected function explicitCodesFirst(array $definitions): array
    {
        $explicit = [];
        $generated = [];

        foreach ($definitions as $value => $definition) {
            $parent = $definition['account']->parent?->value;
            $parentGenerated = $parent !== null && isset($generated[$parent]);

            if ($definition['account']->code === null || $parentGenerated) {
                $generated[$value] = $definition;
            } else {
                $explicit[$value] = $definition;
            }
        }

        return $explicit + $generated;
    }

    /**
     * Create every account of the chart that doesn't exist yet, in one
     * transaction. Accounts are matched by preset within the tenant, and
     * existing ones are never modified, so this is safe to re-run - except
     * with $sync, which refreshes the descriptive fields (name, description,
     * category, sub-category, report section) of existing built-in accounts.
     *
     * A built-in account whose code is already used by an account with no
     * preset and the same type is adopted (tagged with the preset) instead,
     * so charts seeded by earlier versions upgrade in place.
     *
     * $tenantId is used when no tenant is resolved (e.g. when provisioning a
     * tenant from a job or command); a resolved tenant always takes priority.
     *
     * @return array{created: array<string, Account>, adopted: array<string, Account>, synced: array<string, Account>}
     *
     * @throws InvalidPresetDefinitionException|InvalidArgumentException|Throwable
     */
    public function seed(string $industry = 'full', int|string|null $tenantId = null, bool $sync = false, bool $includeBuiltIn = true): array
    {
        $definitions = $this->definitions($industry, $includeBuiltIn);
        $tenantId = app(TenantResolver::class)->resolve() ?? $tenantId;

        return DB::transaction(fn () => ChartStructure::bypass(function () use ($definitions, $tenantId, $sync) {
            $result = ['created' => [], 'adopted' => [], 'synced' => []];

            // Preset values are read from the preset, not the array keys:
            // PHP turns numeric-string keys into ints, which Postgres won't
            // compare with the string `preset` column.
            foreach ($definitions as ['preset' => $preset, 'account' => $definition, 'builtIn' => $builtIn, 'checkCode' => $checkCode]) {
                $value = $preset->value;
                $label = PresetDefinitions::label($preset);

                /** @var Account|null $existing */
                $existing = $this->accountsOf($tenantId)->where('preset', $value)->first();
                if ($existing) {
                    if ($sync && $builtIn && $this->sync($existing, $definition)) {
                        $result['synced'][$value] = $existing;
                    }

                    continue;
                }

                $accountType = $definition->type->accountType();
                if (! $accountType) {
                    throw new InvalidPresetDefinitionException(
                        "Cannot seed {$label}: account type '{$definition->type->value}' doesn't exist. Run AccountTypeSeeder first."
                    );
                }

                $parent = null;
                if ($definition->parent) {
                    /** @var Account|null $parent */
                    $parent = $this->accountsOf($tenantId)->where('preset', $definition->parent->value)->first();
                    if (! $parent) {
                        throw new InvalidPresetDefinitionException(
                            "Cannot seed {$label}: its parent preset ".PresetDefinitions::label($definition->parent).' has no account. Seed or tag that account first.'
                        );
                    }
                }

                $code = $definition->code;
                if ($code !== null) {
                    /** @var Account|null $codeTakenBy */
                    $codeTakenBy = $this->accountsOf($tenantId)->where('code', $code)->first();
                    if ($codeTakenBy) {
                        if ($builtIn && $codeTakenBy->preset === null && $codeTakenBy->account_type_id == $accountType->getKey()) {
                            $codeTakenBy->update(['preset' => $value]);
                            $result['adopted'][$value] = $codeTakenBy;

                            continue;
                        }

                        throw new InvalidPresetDefinitionException(
                            "Cannot seed {$label}: account code '{$code}' is already used by '{$codeTakenBy->name}'."
                        );
                    }

                    if ($checkCode && $parent && ChartStructure::enabled() && ! app(AccountCodeGenerator::class)->fits($code, $parent, $tenantId)) {
                        throw new InvalidPresetDefinitionException(
                            "Cannot seed {$label}: code '{$code}' doesn't fit under '{$parent->name}' ({$parent->code})."
                        );
                    }
                } elseif ($parent) {
                    $code = app(AccountCodeGenerator::class)->next($parent, $tenantId);
                } else {
                    throw new InvalidPresetDefinitionException("Cannot seed {$label}: a root account needs an explicit code.");
                }

                $normalBalance = $definition->normalBalance ?? NormalBalance::from(strtolower($accountType->normal_balance));

                $result['created'][$value] = Account::create([
                    'tenant_id' => $tenantId,
                    'code' => $code,
                    'name' => $definition->name,
                    'account_type_id' => $accountType->getKey(),
                    'parent_id' => $parent?->getKey(),
                    'preset' => $value,
                    // Inherited from the parent unless given, since reports
                    // group by them (e.g. CashFlowStatement by category).
                    'category' => $definition->category ?? $parent?->category,
                    'sub_category' => $definition->subCategory,
                    'report_section' => $definition->reportSection ?? $parent?->report_section ?? $accountType->report_section,
                    // The accounts table stores normal_balance upper-case
                    // ('DEBIT'/'CREDIT'), unlike the NormalBalance enum.
                    'normal_balance' => strtoupper($normalBalance->value),
                    'description' => $definition->description,
                    'allows_manual_entries' => $definition->allowsManualEntries,
                    'currency_code' => config('laravel_accounting.default_currency_code', 'USD'),
                    'is_active' => true,
                    'is_system_account' => $builtIn && $definition->isSystemAccount,
                ]);
            }

            return $result;
        }));
    }

    /**
     * The built-in definitions for an industry, with exclusions and
     * overrides from config applied.
     *
     * @return array<string, array{preset: AccountPresetContract, account: PresetAccount, builtIn: bool, checkCode: bool}>
     *
     * @throws InvalidPresetDefinitionException|InvalidArgumentException
     */
    protected function builtInDefinitions(string $industry): array
    {
        $industries = AccountPreset::byIndustry();
        if (! array_key_exists($industry, $industries)) {
            throw new InvalidArgumentException(
                "Unknown industry '{$industry}'. Expected one of: ".implode(', ', array_keys($industries))
            );
        }

        $all = PresetDefinitions::forEnum(AccountPreset::class);
        $inIndustry = array_flip(array_map(static fn (AccountPreset $preset) => $preset->value, $industries[$industry]));

        $excluded = [];
        foreach (config('laravel_accounting.excluded_presets', []) as $preset) {
            $value = $preset instanceof AccountPresetContract ? $preset->value : $preset;
            if (! isset($all[$value])) {
                throw new InvalidPresetDefinitionException("excluded_presets: '{$value}' isn't a preset of the package's chart.");
            }
            $excluded[$value] = true;
        }
        foreach ($all as $value => $definition) {
            if (! isset($inIndustry[$value])) {
                $excluded[$value] = true;
            }
        }
        foreach (array_keys($excluded) as $value) {
            if (PresetDefinitions::isLocked((string) $value)) {
                throw new InvalidPresetDefinitionException("The root account {$value} can't be excluded from the chart.");
            }
        }

        $included = [];
        foreach (config('laravel_accounting.included_presets', []) as $preset) {
            $value = $preset instanceof AccountPresetContract ? $preset->value : $preset;
            if (! isset($all[$value])) {
                throw new InvalidPresetDefinitionException("included_presets: '{$value}' isn't a preset of the package's chart.");
            }
            $included[$value] = true;
        }

        $overrides = config('laravel_accounting.preset_overrides', []);
        foreach ($overrides as $value => $fields) {
            if (! isset($all[$value])) {
                throw new InvalidPresetDefinitionException("preset_overrides: '{$value}' isn't a preset of the package's chart.");
            }
            if (PresetDefinitions::isLocked((string) $value)) {
                throw new InvalidPresetDefinitionException("preset_overrides: the root account {$value} can't be overridden.");
            }
            if (! is_array($fields) || array_diff(array_keys($fields), self::OVERRIDABLE)) {
                throw new InvalidPresetDefinitionException(
                    "preset_overrides: {$value} may only override ".implode(', ', self::OVERRIDABLE).'.'
                );
            }
        }

        $definitions = [];
        foreach (PresetDefinitions::orderParentsFirst($all) as $value => $definition) {
            $parent = $definition['account']->parent?->value;

            // Leave out excluded accounts and, with them, their branch; and
            // optional accounts that weren't asked for.
            if (isset($excluded[$value]) || ($parent !== null && ! isset($definitions[$parent]))
                || ($definition['account']->optional && ! isset($included[$value]))) {
                continue;
            }

            $account = isset($overrides[$value]) ? $definition['account']->with($overrides[$value]) : $definition['account'];
            $definitions[$value] = [
                'preset' => $definition['preset'],
                'account' => $account,
                'builtIn' => true,
                'checkCode' => isset($overrides[$value]['code']),
            ];
        }

        return $definitions;
    }

    /**
     * Refresh an existing built-in account's descriptive fields from its
     * definition. Fields the definition leaves empty are kept.
     */
    protected function sync(Account $account, PresetAccount $definition): bool
    {
        $parent = $account->parent;

        $account->fill(array_filter([
            'name' => $definition->name,
            'description' => $definition->description,
            'category' => $definition->category ?? $parent?->category,
            'sub_category' => $definition->subCategory,
            'report_section' => $definition->reportSection ?? $parent?->report_section,
        ], static fn ($value) => $value !== null));

        if (! $account->isDirty()) {
            return false;
        }

        return $account->save();
    }

    /**
     * @param  array<string, array{preset: AccountPresetContract, account: PresetAccount, builtIn: bool, checkCode: bool}>  $definitions
     *
     * @throws InvalidPresetDefinitionException
     */
    protected function assertUniqueCodes(array $definitions): void
    {
        $codes = [];
        foreach ($definitions as $definition) {
            $code = $definition['account']->code;
            if ($code === null) {
                continue;
            }

            $label = PresetDefinitions::label($definition['preset']);
            if (isset($codes[$code])) {
                throw new InvalidPresetDefinitionException("{$label} and {$codes[$code]} both define account code '{$code}'.");
            }
            $codes[$code] = $label;
        }
    }

    /**
     * Accounts belonging to exactly this tenant (or to no tenant, for null),
     * bypassing the global scope so a non-strict scope's shared rows, or no
     * scope at all when no tenant is resolved, can't leak in.
     */
    protected function accountsOf(int|string|null $tenantId): Builder
    {
        return Account::withoutGlobalScope(MultiTenantScope::class)->where('tenant_id', $tenantId);
    }
}
