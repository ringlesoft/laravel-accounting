<?php

namespace RingleSoft\LaravelAccounting\Support;

use ReflectionEnum;
use RingleSoft\LaravelAccounting\Attributes\PresetAccount;
use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Exceptions\InvalidPresetDefinitionException;

/**
 * Reads the #[PresetAccount] definitions declared on preset enum cases.
 * Results are cached per enum, since attributes can't change at runtime.
 */
class PresetDefinitions
{
    /**
     * @var array<class-string, array<string, array{preset: AccountPresetContract, account: PresetAccount}>>
     */
    protected static array $cache = [];

    /**
     * @var string[]|null
     */
    protected static ?array $lockedValues = null;

    /**
     * The definitions declared on an enum's cases, in declaration order,
     * keyed by preset value. Cases without #[PresetAccount] are left out.
     *
     * @param  class-string<AccountPresetContract>  $enum
     * @return array<string, array{preset: AccountPresetContract, account: PresetAccount}>
     */
    public static function forEnum(string $enum): array
    {
        if (! isset(static::$cache[$enum])) {
            $definitions = [];
            foreach ((new ReflectionEnum($enum))->getCases() as $case) {
                $attribute = $case->getAttributes(PresetAccount::class)[0] ?? null;
                if ($attribute) {
                    /** @var AccountPresetContract $preset */
                    $preset = $case->getValue();
                    $definitions[$preset->value] = ['preset' => $preset, 'account' => $attribute->newInstance()];
                }
            }
            static::$cache[$enum] = $definitions;
        }

        return static::$cache[$enum];
    }

    /**
     * The definition declared on a preset's case, if any.
     */
    public static function of(AccountPresetContract $preset): ?PresetAccount
    {
        return static::forEnum($preset::class)[$preset->value]['account'] ?? null;
    }

    /**
     * Values of the package chart's root (first-level) presets: the fixed
     * frame of the chart, which users can't change, delete or add to.
     *
     * @return string[]
     */
    public static function lockedValues(): array
    {
        return static::$lockedValues ??= array_values(array_map(
            static fn (array $definition) => $definition['preset']->value,
            array_filter(
                static::forEnum(AccountPreset::class),
                static fn (array $definition) => $definition['account']->parent === null
            )
        ));
    }

    public static function isLocked(?string $presetValue): bool
    {
        return $presetValue !== null && in_array($presetValue, static::lockedValues(), true);
    }

    /**
     * Depth-first ordering so that each definition whose parent is another
     * definition comes after it. Parents outside the definitions (already
     * existing accounts) impose no order.
     *
     * @template T of array{preset: AccountPresetContract, account: PresetAccount}
     *
     * @param  array<string, T>  $definitions
     * @return array<string, T>
     *
     * @throws InvalidPresetDefinitionException A parent cycle
     */
    public static function orderParentsFirst(array $definitions): array
    {
        $ordered = [];
        $visiting = [];

        $visit = static function (string $value) use (&$visit, &$ordered, &$visiting, $definitions): void {
            if (isset($ordered[$value])) {
                return;
            }

            if (isset($visiting[$value])) {
                throw new InvalidPresetDefinitionException(
                    'Preset parents form a cycle: '.implode(' -> ', [...array_keys($visiting), $value]).'.'
                );
            }

            $visiting[$value] = true;
            $parent = $definitions[$value]['account']->parent?->value;
            if ($parent !== null && isset($definitions[$parent])) {
                $visit((string) $parent);
            }
            unset($visiting[$value]);

            $ordered[$value] = $definitions[$value];
        };

        foreach (array_keys($definitions) as $value) {
            $visit((string) $value);
        }

        return $ordered;
    }

    public static function label(AccountPresetContract $preset): string
    {
        return class_basename($preset).'::'.$preset->name;
    }
}
