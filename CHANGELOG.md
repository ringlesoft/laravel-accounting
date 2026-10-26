# Changelog

All notable changes to `laravel-accounting` will be documented in this file.

## 0.1.2

### Added
- Configurable tenant resolution: `tenant_resolver` config and `Contracts\TenantResolver`, so jobs, commands and seeders can run with no logged-in user.
- Custom account presets: string-backed enums implementing `Contracts\AccountPresetContract` work in templates, `resolveAccount()`, `ResolvesAccountPresets` and `AccountService`.
- `#[PresetAccount]` attribute and `custom_presets` config: custom preset accounts are seeded together with the package's chart.
- Chart structure rules (`enforce_chart_structure`, on by default): the 11 root accounts are locked, every other account needs a parent, and codes are generated under the parent when omitted (explicit codes must fit).
- `ChartOfAccountsService` (`LaravelAccounting::chart()`), with `excluded_presets`, `preset_overrides` and an opt-in `sync` mode.
- The chart seeds `INCOME_SUMMARY`, `OUTPUT_VAT` and `BOND_ISSUANCE_COSTS`, which `ClosingService`, `VatPaymentTemplate` and `BondIssuanceTemplate` need.
- Per-call chart adjustments: `ChartOfAccountsService::seed()` and `definitions()` accept `excluded`, `included`, `overrides` and `customPresets`, falling back to config.
- Optional chart accounts for the other catalogued presets (`CASH_ON_HAND`, `ALLOWANCE_FOR_BAD_DEBTS`, `WITHHOLDING_TAX_*`, ...), seeded when listed in `included_presets`.

### Changed
- The chart of accounts is defined by `#[PresetAccount]` attributes on `AccountPreset`, and `ChartOfAccountsSeeder` is now a thin wrapper. The 11 accounts that had no preset now carry one (`CURRENT_ASSETS`, `EQUITY`, `REVENUE`, ...); charts seeded by earlier versions are adopted on re-seed.
- Account-code uniqueness is checked per tenant.
- A calendar-aligned fiscal year is named `2026` instead of `2026-2026`.
- `HasUuid` is applied to all models.
- Faster chart seeding: about 200 queries for the full chart instead of about 2,000, and cheaper code generation when creating accounts without a code.

### Fixed
- Primary key definitions on Postgres (accounts and journal entries tables).
- `PeriodService` copies the tenant onto created periods and matches dates correctly on SQLite.
- The installer no longer overwrites an existing config, and applies the chosen UUID setting to the migrations it runs.
- `AccountTypeEnum::getId()` returns UUID keys instead of failing when `use_uuid` is on.
- `HasUuid` reads `use_uuid` when a record is created, so models booted earlier (e.g. `Account`) still get UUIDs.

### Breaking
- Creating an account without a parent fails unless `enforce_chart_structure` is `false`.
- Preset type hints are now `AccountPresetContract`: implementations of `ResolvesAccountPresets` (and template overrides of `debit()`, `credit()`, `resolveAccount()`) must update their signatures.
- New preset values (`EQUITY`, `REVENUE`, ...) may collide with existing custom presets.
