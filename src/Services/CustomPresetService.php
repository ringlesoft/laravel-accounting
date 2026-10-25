<?php

namespace RingleSoft\LaravelAccounting\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use ReflectionEnum;
use RingleSoft\LaravelAccounting\Attributes\PresetAccount;
use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Contracts\TenantResolver;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Enums\NormalBalance;
use RingleSoft\LaravelAccounting\Exceptions\InvalidPresetDefinitionException;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Scopes\MultiTenantScope;
use Throwable;

/**
 * Custom preset enums registered in `laravel_accounting.custom_presets`, and
 * seeding the accounts their #[PresetAccount] cases describe.
 */
class CustomPresetService
{
    /**
     * The registered custom preset enums, validated: each must be a
     * string-backed enum implementing AccountPresetContract, and no case
     * value may repeat a built-in or another registered preset's value.
     *
     * @return array<class-string<AccountPresetContract>>
     *
     * @throws InvalidPresetDefinitionException
     */
    public function enums(): array
    {
        $enums = array_values(array_unique(config('laravel_accounting.custom_presets', [])));

        foreach ($enums as $enum) {
            if (! is_string($enum) || ! enum_exists($enum)) {
                throw new InvalidPresetDefinitionException('Custom preset '.var_export($enum, true).' is not an enum.');
            }

            if (! is_subclass_of($enum, AccountPresetContract::class)) {
                throw new InvalidPresetDefinitionException("Custom preset enum {$enum} must implement ".AccountPresetContract::class.'.');
            }

            if ((string) (new ReflectionEnum($enum))->getBackingType() !== 'string') {
                throw new InvalidPresetDefinitionException("Custom preset enum {$enum} must be string-backed.");
            }
        }

        $this->assertUniqueValues($enums);

        return $enums;
    }

    /**
     * Every case of the registered custom preset enums.
     *
     * @return AccountPresetContract[]
     *
     * @throws InvalidPresetDefinitionException
     */
    public function presets(): array
    {
        return array_merge([], ...array_map(static fn (string $enum) => $enum::cases(), $this->enums()));
    }

    /**
     * The accounts to seed, keyed by preset value and ordered so that a
     * custom parent always comes before its children.
     *
     * @return array<string, array{preset: AccountPresetContract, account: PresetAccount}>
     *
     * @throws InvalidPresetDefinitionException
     */
    public function definitions(): array
    {
        $definitions = [];
        $codes = [];

        foreach ($this->enums() as $enum) {
            foreach ((new ReflectionEnum($enum))->getCases() as $case) {
                $attribute = $case->getAttributes(PresetAccount::class)[0] ?? null;
                if (! $attribute) {
                    continue;
                }

                /** @var AccountPresetContract $preset */
                $preset = $case->getValue();
                $account = $attribute->newInstance();
                $label = $this->label($preset);

                if (isset($codes[$account->code])) {
                    throw new InvalidPresetDefinitionException(
                        "{$label} and {$codes[$account->code]} both define account code '{$account->code}'."
                    );
                }
                $codes[$account->code] = $label;

                $definitions[$preset->value] = ['preset' => $preset, 'account' => $account];
            }
        }

        return $this->orderParentsFirst($definitions);
    }

    /**
     * Create the account for every #[PresetAccount] case that doesn't have
     * one yet, in a single transaction. Existing accounts (matched by preset
     * within the tenant) are never modified, so this is safe to re-run.
     *
     * $tenantId is used when no tenant is resolved (e.g. when provisioning a
     * tenant from a job or command); a resolved tenant always takes priority.
     *
     * @return array<string, Account> The accounts created, keyed by preset value
     *
     * @throws InvalidPresetDefinitionException|Throwable
     */
    public function seed(int|string|null $tenantId = null): array
    {
        $definitions = $this->definitions();
        $tenantId = app(TenantResolver::class)->resolve() ?? $tenantId;

        return DB::transaction(function () use ($definitions, $tenantId) {
            $created = [];

            // Keys aren't used as preset values: PHP turns numeric-string keys
            // into ints, which Postgres won't compare with the string column.
            foreach ($definitions as ['preset' => $preset, 'account' => $definition]) {
                $value = $preset->value;

                if ($this->accountsOf($tenantId)->where('preset', $value)->exists()) {
                    continue;
                }

                $label = $this->label($preset);

                /** @var Account|null $codeTakenBy */
                $codeTakenBy = $this->accountsOf($tenantId)->where('code', $definition->code)->first();
                if ($codeTakenBy) {
                    throw new InvalidPresetDefinitionException(
                        "Cannot seed {$label}: account code '{$definition->code}' is already used by '{$codeTakenBy->name}'."
                    );
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
                            "Cannot seed {$label}: its parent preset {$this->label($definition->parent)} has no account. Seed or tag that account first."
                        );
                    }
                }

                $normalBalance = $definition->normalBalance ?? NormalBalance::from(strtolower($accountType->normal_balance));

                $created[$value] = Account::create([
                    'tenant_id' => $tenantId,
                    'code' => $definition->code,
                    'name' => $definition->name,
                    'account_type_id' => $accountType->getKey(),
                    'parent_id' => $parent?->getKey(),
                    'preset' => $value,
                    // Inherited from the parent unless given, since reports
                    // group by them (e.g. CashFlowStatement by category).
                    'category' => $definition->category ?? $parent?->category,
                    'report_section' => $definition->reportSection ?? $parent?->report_section ?? $accountType->report_section,
                    // The accounts table stores normal_balance upper-case
                    // ('DEBIT'/'CREDIT'), unlike the NormalBalance enum.
                    'normal_balance' => strtoupper($normalBalance->value),
                    'description' => $definition->description,
                    'allows_manual_entries' => $definition->allowsManualEntries,
                    'currency_code' => config('laravel_accounting.default_currency_code', 'USD'),
                    'is_active' => true,
                    'is_system_account' => false,
                ]);
            }

            return $created;
        });
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

    /**
     * @param  array<class-string<AccountPresetContract>>  $enums
     *
     * @throws InvalidPresetDefinitionException
     */
    protected function assertUniqueValues(array $enums): void
    {
        $owners = [];
        foreach (AccountPreset::cases() as $preset) {
            $owners[$preset->value] = $this->label($preset);
        }

        foreach ($enums as $enum) {
            foreach ($enum::cases() as $preset) {
                if (isset($owners[$preset->value])) {
                    throw new InvalidPresetDefinitionException(
                        "{$this->label($preset)} reuses the value '{$preset->value}' of {$owners[$preset->value]}. Preset values must be unique."
                    );
                }
                $owners[$preset->value] = $this->label($preset);
            }
        }
    }

    /**
     * Depth-first ordering so that each definition whose parent is another
     * definition comes after it. Parents outside the definitions (built-in or
     * already-existing presets) impose no order.
     *
     * @param  array<string, array{preset: AccountPresetContract, account: PresetAccount}>  $definitions
     * @return array<string, array{preset: AccountPresetContract, account: PresetAccount}>
     *
     * @throws InvalidPresetDefinitionException
     */
    protected function orderParentsFirst(array $definitions): array
    {
        $ordered = [];
        $visiting = [];

        $visit = function (string $value) use (&$visit, &$ordered, &$visiting, $definitions): void {
            if (isset($ordered[$value])) {
                return;
            }

            if (isset($visiting[$value])) {
                throw new InvalidPresetDefinitionException(
                    'Custom preset parents form a cycle: '.implode(' -> ', [...array_keys($visiting), $value]).'.'
                );
            }

            $visiting[$value] = true;
            $parent = $definitions[$value]['account']->parent?->value;
            if ($parent !== null && isset($definitions[$parent])) {
                $visit($parent);
            }
            unset($visiting[$value]);

            $ordered[$value] = $definitions[$value];
        };

        foreach (array_keys($definitions) as $value) {
            $visit((string) $value);
        }

        return $ordered;
    }

    protected function label(AccountPresetContract $preset): string
    {
        return class_basename($preset).'::'.$preset->name;
    }
}
