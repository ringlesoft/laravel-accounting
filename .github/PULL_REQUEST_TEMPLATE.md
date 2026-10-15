## What changed

<!-- One or two sentences. One logical change per PR. -->

## Why

<!-- The problem this solves, or the behavior this adds. Link an issue if there is one. -->

## How this was verified

<!-- A failing-then-passing test, or a manual repro against a seeded chart of accounts (commands/output). "It works" alone isn't enough for anything touching posting or report correctness. -->

## Checklist

- [ ] `composer test` passes
- [ ] `composer analyse` passes (or the new PHPStan errors are pre-existing/unrelated)
- [ ] `composer format` run (Pint)
- [ ] `docs/templates-api.md` / `docs/financial-transactions.md` updated if this changes a template's behavior
- [ ] No TODO comments left in the diff without an issue reference
