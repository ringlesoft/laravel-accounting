<?php

namespace RingleSoft\LaravelAccounting\Services;

use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelAccounting\Contracts\AccountPresetContract;
use RingleSoft\LaravelAccounting\DTOs\ClosingEntryPreview;
use RingleSoft\LaravelAccounting\DTOs\ClosingResult;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum;
use RingleSoft\LaravelAccounting\Events\IncomeClosed;
use RingleSoft\LaravelAccounting\Exceptions\AccountNotFoundException;
use RingleSoft\LaravelAccounting\Exceptions\PeriodClosedException;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use Throwable;

/**
 * Posts the standard period-end closing entries (close Revenue and Expenses
 * into Income Summary, close Income Summary's net result into Retained
 * Earnings, close Dividends Declared into Retained Earnings) - the thing
 * missing that keeps the Balance Sheet from balancing at year-end.
 *
 * Every commit*() method posts through the normal JournalService
 * createDraft()+post() path - nothing here bypasses it. Every preview*()
 * method is a pure read: it builds the exact VoucherData that would be
 * posted, without writing anything.
 *
 * Each of the four steps reads the ledger's actual current state rather
 * than a cached calculation, so calling them in any order, any number of
 * times, is safe: a step with nothing left to close (because it was
 * already closed, or there was never anything there) simply returns null.
 */
class ClosingService
{
    public function __construct(
        protected BalanceService $balanceService,
        protected AccountService $accountService,
        protected JournalService $journalService,
    ) {}

    // ---------------------------------------------------------------
    // Combined
    // ---------------------------------------------------------------

    public function preview(FiscalPeriod $period): ClosingEntryPreview
    {
        $closeRevenue = $this->previewCloseRevenue($period);
        $closeExpenses = $this->previewCloseExpenses($period);
        $closeIncomeSummary = $this->previewCloseIncomeSummary($period);
        $closeDividends = $this->previewCloseDividends($period);

        $totalRevenue = $this->revenueTotal($period);
        $totalExpenses = $this->expenseTotal($period);

        return new ClosingEntryPreview(
            period: $period,
            closeRevenue: $closeRevenue,
            closeExpenses: $closeExpenses,
            closeIncomeSummary: $closeIncomeSummary,
            closeDividends: $closeDividends,
            totalRevenue: $totalRevenue,
            totalExpenses: $totalExpenses,
            netIncome: round($totalRevenue - $totalExpenses, 2),
            dividendsDeclared: $this->dividendsDeclaredTotal($period),
        );
    }

    /**
     * All four steps in one DB transaction - either the whole close
     * succeeds or none of it does. Recomputes everything fresh rather than
     * trusting a previously-captured preview, consistent with every
     * granular commit*() method below.
     *
     * @throws PeriodClosedException
     * @throws AccountNotFoundException
     * @throws Throwable
     */
    public function commit(FiscalPeriod $period, ?int $userId = null): ClosingResult
    {
        return DB::transaction(function () use ($period, $userId) {
            $this->guardPeriodOpen($period);

            $preview = $this->preview($period);

            return new ClosingResult(
                period: $period,
                closeRevenueEntry: $this->commitCloseRevenue($period, $userId),
                closeExpensesEntry: $this->commitCloseExpenses($period, $userId),
                closeIncomeSummaryEntry: $this->commitCloseIncomeSummary($period, $userId),
                closeDividendsEntry: $this->commitCloseDividends($period, $userId),
                totalRevenue: $preview->totalRevenue,
                totalExpenses: $preview->totalExpenses,
                netIncome: $preview->netIncome,
                dividendsDeclared: $preview->dividendsDeclared,
            );
        });
    }

    // ---------------------------------------------------------------
    // Close Revenue
    // ---------------------------------------------------------------

    /**
     * @throws AccountNotFoundException INCOME_SUMMARY not seeded, and there is revenue to close
     */
    public function previewCloseRevenue(FiscalPeriod $period): ?VoucherData
    {
        $incomeType = AccountTypeEnum::INCOME->accountType();
        if (! $incomeType) {
            return null;
        }

        $balances = $this->balanceService->getPeriodBalancesByAccountType($incomeType, $period->start_date, $period->end_date);

        $voucher = $this->voucherFor($period, "Close revenue to Income Summary - {$period->name}");
        $total = 0.0;

        foreach ($balances as $row) {
            $amount = round($row['balance'], 2);
            if (abs($amount) < 0.01) {
                continue;
            }

            // Normal credit balance -> debit it to zero. An abnormal debit
            // balance (e.g. a contra-revenue account) -> credit it instead.
            if ($amount > 0) {
                $voucher->debit($row['account_id'], $amount, "Close {$row['name']}");
            } else {
                $voucher->credit($row['account_id'], abs($amount), "Close {$row['name']}");
            }

            $total += $amount;
        }

        if (abs($total) < 0.01) {
            return null;
        }

        $incomeSummary = $this->resolveClosingAccount(AccountPreset::INCOME_SUMMARY);

        if ($total > 0) {
            $voucher->credit($incomeSummary->id, $total, 'Total revenue closed');
        } else {
            $voucher->debit($incomeSummary->id, abs($total), 'Total revenue closed');
        }

        return $voucher;
    }

    /**
     * @throws PeriodClosedException
     * @throws AccountNotFoundException
     * @throws Throwable
     */
    public function commitCloseRevenue(FiscalPeriod $period, ?int $userId = null): ?JournalEntry
    {
        $this->guardPeriodOpen($period);

        $voucher = $this->previewCloseRevenue($period);
        if (! $voucher) {
            return null;
        }

        $entry = $this->journalService->createDraft($voucher, $userId);
        $this->journalService->post($entry, $userId);

        return $entry;
    }

    // ---------------------------------------------------------------
    // Close Expenses
    // ---------------------------------------------------------------

    /**
     * @throws AccountNotFoundException INCOME_SUMMARY not seeded, and there are expenses to close
     */
    public function previewCloseExpenses(FiscalPeriod $period): ?VoucherData
    {
        $expenseType = AccountTypeEnum::EXPENSE->accountType();
        if (! $expenseType) {
            return null;
        }

        $balances = $this->balanceService->getPeriodBalancesByAccountType($expenseType, $period->start_date, $period->end_date);

        $voucher = $this->voucherFor($period, "Close expenses to Income Summary - {$period->name}");
        $total = 0.0;

        foreach ($balances as $row) {
            $amount = round($row['balance'], 2);
            if (abs($amount) < 0.01) {
                continue;
            }

            // Normal debit balance -> credit it to zero. An abnormal credit
            // balance (e.g. a contra-expense/recovery account) -> debit it.
            if ($amount > 0) {
                $voucher->credit($row['account_id'], $amount, "Close {$row['name']}");
            } else {
                $voucher->debit($row['account_id'], abs($amount), "Close {$row['name']}");
            }

            $total += $amount;
        }

        if (abs($total) < 0.01) {
            return null;
        }

        $incomeSummary = $this->resolveClosingAccount(AccountPreset::INCOME_SUMMARY);

        if ($total > 0) {
            $voucher->debit($incomeSummary->id, $total, 'Total expenses closed');
        } else {
            $voucher->credit($incomeSummary->id, abs($total), 'Total expenses closed');
        }

        return $voucher;
    }

    /**
     * @throws PeriodClosedException
     * @throws AccountNotFoundException
     * @throws Throwable
     */
    public function commitCloseExpenses(FiscalPeriod $period, ?int $userId = null): ?JournalEntry
    {
        $this->guardPeriodOpen($period);

        $voucher = $this->previewCloseExpenses($period);
        if (! $voucher) {
            return null;
        }

        $entry = $this->journalService->createDraft($voucher, $userId);
        $this->journalService->post($entry, $userId);

        return $entry;
    }

    // ---------------------------------------------------------------
    // Close Income Summary -> Retained Earnings
    // ---------------------------------------------------------------

    /**
     * Reads Income Summary's own posted ledger movement for the period -
     * not a recalculation from Revenue/Expense balances, which would read
     * as zero once those are already closed. This is what makes calling
     * this step independently of previewCloseRevenue()/previewCloseExpenses()
     * safe and order-independent: it reflects the ledger's actual state,
     * whatever that is, rather than assuming a particular call sequence.
     *
     * @throws AccountNotFoundException INCOME_SUMMARY not seeded
     */
    public function previewCloseIncomeSummary(FiscalPeriod $period): ?VoucherData
    {
        $incomeSummary = $this->resolveClosingAccount(AccountPreset::INCOME_SUMMARY);
        $movement = $this->balanceService->getPeriodMovement($incomeSummary, $period);

        $netCredit = round($movement['total_credits'] - $movement['total_debits'], 2);
        if (abs($netCredit) < 0.01) {
            return null;
        }

        $retainedEarnings = $this->resolveClosingAccount(AccountPreset::RETAINED_EARNINGS);
        $isProfit = $netCredit > 0;
        $amount = abs($netCredit);
        $label = $isProfit ? 'Close net income' : 'Close net loss';

        $voucher = $this->voucherFor($period, "{$label} to Retained Earnings - {$period->name}");

        if ($isProfit) {
            $voucher->debit($incomeSummary->id, $amount, $label);
            $voucher->credit($retainedEarnings->id, $amount, $label);
        } else {
            $voucher->debit($retainedEarnings->id, $amount, $label);
            $voucher->credit($incomeSummary->id, $amount, $label);
        }

        return $voucher;
    }

    /**
     * @throws PeriodClosedException
     * @throws AccountNotFoundException
     * @throws Throwable
     */
    public function commitCloseIncomeSummary(FiscalPeriod $period, ?int $userId = null): ?JournalEntry
    {
        $this->guardPeriodOpen($period);

        // Captured before posting: the entry we're about to post is itself
        // dated inside the period, so reading this again afterward would
        // show the now-closed (zeroed) result instead of what we just closed.
        $incomeSummary = $this->resolveClosingAccount(AccountPreset::INCOME_SUMMARY);
        $movement = $this->balanceService->getPeriodMovement($incomeSummary, $period);
        $netIncome = round($movement['total_credits'] - $movement['total_debits'], 2);

        $voucher = $this->previewCloseIncomeSummary($period);
        if (! $voucher) {
            return null;
        }

        $entry = $this->journalService->createDraft($voucher, $userId);
        $this->journalService->post($entry, $userId);

        event(new IncomeClosed($period, $netIncome, $userId));

        return $entry;
    }

    // ---------------------------------------------------------------
    // Close Dividends -> Retained Earnings
    // ---------------------------------------------------------------

    /**
     * Unlike Income Summary/Retained Earnings, a missing DIVIDENDS_DECLARED
     * account is not a setup error - most businesses never declare
     * dividends at all. Returns null rather than throwing.
     */
    public function previewCloseDividends(FiscalPeriod $period): ?VoucherData
    {
        $dividendsAccount = $this->accountService->findByPreset(AccountPreset::DIVIDENDS_DECLARED);
        if (! $dividendsAccount) {
            return null;
        }

        $movement = $this->balanceService->getPeriodMovement($dividendsAccount, $period);
        $netDebit = round($movement['total_debits'] - $movement['total_credits'], 2);

        if ($netDebit < 0.01) {
            return null;
        }

        $retainedEarnings = $this->resolveClosingAccount(AccountPreset::RETAINED_EARNINGS);

        return $this->voucherFor($period, "Close dividends to Retained Earnings - {$period->name}")
            ->debit($retainedEarnings->id, $netDebit, 'Close dividends declared')
            ->credit($dividendsAccount->id, $netDebit, 'Close dividends declared');
    }

    /**
     * @throws PeriodClosedException
     * @throws AccountNotFoundException RETAINED_EARNINGS not seeded, and there are dividends to close
     * @throws Throwable
     */
    public function commitCloseDividends(FiscalPeriod $period, ?int $userId = null): ?JournalEntry
    {
        $this->guardPeriodOpen($period);

        $voucher = $this->previewCloseDividends($period);
        if (! $voucher) {
            return null;
        }

        $entry = $this->journalService->createDraft($voucher, $userId);
        $this->journalService->post($entry, $userId);

        return $entry;
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    protected function voucherFor(FiscalPeriod $period, string $description): VoucherData
    {
        return VoucherData::make()
            ->describe($description)
            ->on($period->end_date)
            ->currency(config('laravel_accounting.default_currency_code', 'USD'));
    }

    /**
     * @throws AccountNotFoundException
     */
    protected function resolveClosingAccount(AccountPresetContract $preset): Account
    {
        $account = $this->accountService->findByPreset($preset);

        if (! $account) {
            throw new AccountNotFoundException(
                "No account found for preset {$preset->value}. Seed or tag an account with this preset before using ClosingService."
            );
        }

        return $account;
    }

    protected function guardPeriodOpen(FiscalPeriod $period): void
    {
        if ($period->is_closed) {
            throw new PeriodClosedException(
                "Fiscal period '{$period->name}' is already closed - closing entries must be posted before the period is closed."
            );
        }
    }

    protected function revenueTotal(FiscalPeriod $period): float
    {
        $incomeType = AccountTypeEnum::INCOME->accountType();
        if (! $incomeType) {
            return 0.0;
        }

        return round(
            $this->balanceService->getPeriodBalancesByAccountType($incomeType, $period->start_date, $period->end_date)->sum('balance'),
            2
        );
    }

    protected function expenseTotal(FiscalPeriod $period): float
    {
        $expenseType = AccountTypeEnum::EXPENSE->accountType();
        if (! $expenseType) {
            return 0.0;
        }

        return round(
            $this->balanceService->getPeriodBalancesByAccountType($expenseType, $period->start_date, $period->end_date)->sum('balance'),
            2
        );
    }

    protected function dividendsDeclaredTotal(FiscalPeriod $period): float
    {
        $dividendsAccount = $this->accountService->findByPreset(AccountPreset::DIVIDENDS_DECLARED);
        if (! $dividendsAccount) {
            return 0.0;
        }

        $movement = $this->balanceService->getPeriodMovement($dividendsAccount, $period);

        return round(max(0.0, $movement['total_debits'] - $movement['total_credits']), 2);
    }
}
