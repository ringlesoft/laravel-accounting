# Changelog

All notable changes to `laravel-accounting` will be documented in this file.

## 0.1.2

### Added
- Configurable tenant resolution: `tenant_resolver` config and `Contracts\TenantResolver`, so jobs, commands and seeders can run with no logged-in user.
- Custom account presets: string-backed enums implementing `Contracts\AccountPresetContract` work in templates, `resolveAccount()`, `ResolvesAccountPresets` and `AccountService`.
- `#[PresetAccount]` attribute and `custom_presets` config: custom preset accounts are seeded together with the package's chart.
- Chart structure rules (`enforce_chart_structure`, on by default): the 11 root accounts are locked, every other account needs a parent, and codes are generated under the parent when omitted (explicit codes must fit).
- `ChartOfAccountsService` (`LaravelAccounting::chart()`), with `excluded_presets`, `preset_overrides` and an opt-in `sync` mode.

### Changed
- The chart of accounts is defined by `#[PresetAccount]` attributes on `AccountPreset`, and `ChartOfAccountsSeeder` is now a thin wrapper. The 11 accounts that had no preset now carry one (`CURRENT_ASSETS`, `EQUITY`, `REVENUE`, ...); charts seeded by earlier versions are adopted on re-seed.
- Account-code uniqueness is checked per tenant.
- A calendar-aligned fiscal year is named `2026` instead of `2026-2026`.
- `HasUuid` is applied to all models.

### Fixed
- Primary key definitions on Postgres (accounts and journal entries tables).
- `PeriodService` copies the tenant onto created periods and matches dates correctly on SQLite.
- The installer no longer overwrites an existing config, and applies the chosen UUID setting to the migrations it runs.

### Breaking
- Creating an account without a parent fails unless `enforce_chart_structure` is `false`.
- Preset type hints are now `AccountPresetContract`: implementations of `ResolvesAccountPresets` (and template overrides of `debit()`, `credit()`, `resolveAccount()`) must update their signatures.
- New preset values (`EQUITY`, `REVENUE`, ...) may collide with existing custom presets.
