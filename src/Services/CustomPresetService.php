<?php

namespace RingleSoft\LaravelAccounting\Services;

use ReflectionEnum;
use RingleSoft\LaravelAccounting\Attributes\PresetAccount;
use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidPresetDefinitionException;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Support\ChartStructure;
use RingleSoft\LaravelAccounting\Support\PresetDefinitions;
use Throwable;

/**
 * Custom preset enums registered in `laravel_accounting.custom_presets`, and
 * the accounts their #[PresetAccount] cases describe. Seeding itself is done
 * by ChartOfAccountsService, together with the package's own chart.
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

            if ($enum === AccountPreset::class) {
                throw new InvalidPresetDefinitionException(AccountPreset::class." is the package's own chart; don't list it in custom_presets.");
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
     * The accounts the registered enums define, keyed by preset value and
     * ordered so that a custom parent always comes before its children.
     *
     * While `enforce_chart_structure` is on, custom accounts can't be root
     * accounts, so each must name a parent. Codes are optional: they're
     * generated under the parent when omitted.
     *
     * @return array<string, array{preset: AccountPresetContract, account: PresetAccount}>
     *
     * @throws InvalidPresetDefinitionException
     */
    public function definitions(): array
    {
        $definitions = [];

        foreach ($this->enums() as $enum) {
            foreach (PresetDefinitions::forEnum($enum) as $value => $definition) {
                $label = PresetDefinitions::label($definition['preset']);

                if ($definition['account']->parent === null && ChartStructure::enabled()) {
                    throw new InvalidPresetDefinitionException(
                        "{$label} must name a parent: root accounts are defined by the package's chart only."
                    );
                }

                if ($definition['account']->isSystemAccount || $definition['account']->optional) {
                    throw new InvalidPresetDefinitionException(
                        "{$label} can't set isSystemAccount or optional: those flags are reserved for the package's own chart."
                    );
                }

                $definitions[$value] = $definition;
            }
        }

        return PresetDefinitions::orderParentsFirst($definitions);
    }

    /**
     * Create the accounts of the registered custom presets that don't exist
     * yet, under parents that must already exist (e.g. the seeded chart).
     * ChartOfAccountsService::seed() seeds the whole chart, these included.
     *
     * @return array<string, Account> The accounts created, keyed by preset value
     *
     * @throws InvalidPresetDefinitionException|Throwable
     */
    public function seed(int|string|null $tenantId = null): array
    {
        return app(ChartOfAccountsService::class)->seed(tenantId: $tenantId, includeBuiltIn: false)['created'];
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
            $owners[$preset->value] = PresetDefinitions::label($preset);
        }

        foreach ($enums as $enum) {
            foreach ($enum::cases() as $preset) {
                if (isset($owners[$preset->value])) {
                    throw new InvalidPresetDefinitionException(
                        PresetDefinitions::label($preset)." reuses the value '{$preset->value}' of {$owners[$preset->value]}. Preset values must be unique."
                    );
                }
                $owners[$preset->value] = PresetDefinitions::label($preset);
            }
        }
    }
}
