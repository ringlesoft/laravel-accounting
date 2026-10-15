# Contributing

Contributions are welcome — bug fixes, new templates, report improvements, and documentation fixes alike.

## Setup

```bash
git clone https://github.com/ringlesoft/laravel-accounting.git
cd laravel-accounting
composer install
```

The package uses [Orchestra Testbench](https://github.com/orchestra/testbench), so there's no separate host Laravel app to set up — tests run against an in-memory skeleton app bootstrapped by Testbench itself.

## Running checks locally

```bash
composer test            # Pest test suite
composer test-coverage   # Pest with coverage report
composer analyse          # PHPStan (see phpstan.neon.dist for the configured level)
composer format           # Laravel Pint — fixes code style issues in place
```

Run `composer format` before committing; CI (`fix-php-code-style-issues.yml`) will otherwise auto-commit style fixes to your PR branch, which is noisy. CI also runs Pest (`run-tests.yml`) and PHPStan (`phpstan.yml`) on every push — a PR won't be considered until both pass.

## Code style

- Follow the existing style in the file you're editing; Pint (Laravel preset) enforces formatting automatically — don't fight it manually.
- Prefer `AccountPreset` enum references over hardcoded account IDs or codes anywhere a template or service resolves an account.
- New templates extend `AccountingTemplate` and implement `configure()`; only add `validate()` if the template has a parameter that could silently produce a wrong (not just rejected) posting — see `docs/templates-api.md` for the pattern.
- Keep multi-tenancy and multi-currency support in mind: any model holding financial data should use the `MultiTenant` trait, and amounts should flow through `VoucherData`/`VoucherDataItem` rather than being written directly to `JournalEntryItem`.

## Adding or changing a template

1. Add the template class under `src/Templates/`.
2. Register it in `src/Templates/TemplateRegistry.php` if it should be resolvable by key or selectable in a UI picker.
3. Add it to `docs/templates-api.md` (its exact debit/credit accounts and parameters) and cross-reference it in `docs/financial-transactions.md` if it corresponds to one of the standard transactions listed there.
4. If a parameter could produce an incorrect (not just invalid) posting — a rate outside a sane range, an amount that could exceed the transaction total — add a `validate()` override that rejects it.

## Reporting bugs

Open an issue with: the package version, a minimal reproduction (ideally a `postWithTemplate()`/`VoucherData` call), the exact exception/output you got, and what you expected instead. For anything touching real financial data correctness (wrong trial balance, wrong report totals), include the actual posted journal entries, not just the symptom.

## Security issues

Do not open a public issue. Email ringunger@gmail.com instead — see [README.md](README.md#security).

## Pull requests

- One logical change per PR — a bug fix and an unrelated refactor should be separate PRs.
- Update `docs/templates-api.md` or `docs/financial-transactions.md` alongside any change to template behavior; a PR that changes what a template does without updating its documented behavior will be asked to add it.
- Add or update a Pest test covering the change where practical.
- Describe in the PR body: what changed, why, and how you verified it (a failing-then-passing test, or a manual repro against a seeded chart of accounts).
