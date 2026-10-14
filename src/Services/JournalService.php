<?php

namespace RingleSoft\LaravelAccounting\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\Enums\EntryType;
use RingleSoft\LaravelAccounting\Events\JournalEntryCreated;
use RingleSoft\LaravelAccounting\Events\JournalEntryPosted;
use RingleSoft\LaravelAccounting\Events\JournalEntryReversed;
use RingleSoft\LaravelAccounting\Events\JournalEntryVoided;
use RingleSoft\LaravelAccounting\Exceptions\DuplicatePostingException;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Exceptions\JournalNotBalancedException;
use RingleSoft\LaravelAccounting\Exceptions\ParentAccountPostingException;
use RingleSoft\LaravelAccounting\Exceptions\PeriodClosedException;
use RingleSoft\LaravelAccounting\Models\FiscalPeriod;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Models\JournalEntryItem;
use Throwable;

class JournalService
{
    /**
     * Create a draft journal entry from voucher data
     *
     * @throws Throwable
     */
    public function createDraft(VoucherData $voucher, int|string|null $userId = null): JournalEntry
    {
        // Retried because the transaction_number race (two requests creating the
        // very first entry of the day with nothing yet to lock) surfaces as a
        // unique constraint violation on transaction_number rather than a deadlock,
        // so Laravel's own DB::transaction() retry logic won't catch it.
        return retry(3, function () use ($voucher, $userId) {
            return $this->createDraftOnce($voucher, $userId);
        }, 0, function (Throwable $exception) {
            return $exception instanceof QueryException
                && str_contains($exception->getMessage(), 'transaction_number');
        });
    }

    /**
     * @throws Throwable
     */
    protected function createDraftOnce(VoucherData $voucher, int|string|null $userId = null): JournalEntry
    {
        return DB::transaction(function () use ($voucher, $userId) {
            $this->validateVoucher($voucher);

            $fiscalPeriod = $this->findFiscalPeriod($voucher->date);

            $journalData = [
                'transaction_date' => $voucher->date ?? now(),
                'description' => $voucher->description,
                'reference' => $voucher->reference,
                'currency_code' => $voucher->currency_code ?? config('laravel_accounting.default_currency_code'),
                'fiscal_period_id' => $fiscalPeriod?->id,
                'posted_at' => null,
                'posted_by_user_id' => $userId ?? auth()->id(),
                'created_by_user_id' => $userId ?? auth()->id(),
                'is_posted' => false,
                'metadata' => $voucher->metadata ?? [],
            ];

            if (isset($voucher->sourceModel) && ($keyName = $voucher->sourceModel->getKeyName()) && ($modalId = $voucher->sourceModel->$keyName)) {
                $journalData['postable_id'] = $modalId;
                $journalData['postable_type'] = get_class($voucher->sourceModel);
            }

            $journalEntry = JournalEntry::create($journalData);

            foreach ($voucher->entries as $entry) {
                JournalEntryItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $entry->accountId,
                    'description' => $entry->memo,
                    'debit_amount' => $entry->isDebit() ? abs($entry->amount) : 0,
                    'credit_amount' => $entry->isCredit() ? abs($entry->amount) : 0,
                    'currency_code' => $journalEntry->currency_code,
                ]);
            }

            $journalEntry->load('items');

            event(new JournalEntryCreated($journalEntry, $userId));

            return $journalEntry;
        });
    }

    /**
     * Validate that journal entry is balanced
     *
     * @throws JournalNotBalancedException
     */
    public function validateBalance(JournalEntry $entry): bool
    {
        $totalDebits = $entry->items->sum('debit_amount');
        $totalCredits = $entry->items->sum('credit_amount');

        $difference = abs($totalDebits - $totalCredits);

        if ($difference > 0.01) {
            throw new JournalNotBalancedException(
                "Journal entry is not balanced. Debits: {$totalDebits}, Credits: {$totalCredits}"
            );
        }

        return true;
    }

    /**
     * Validate that the fiscal period is open
     *
     * @throws PeriodClosedException
     */
    public function validatePeriod(JournalEntry $entry): bool
    {
        if (! $entry->fiscalPeriod) {
            throw new PeriodClosedException('No fiscal period found for this transaction date');
        }

        if ($entry->fiscalPeriod->is_closed) {
            throw new PeriodClosedException(
                "Fiscal period '{$entry->fiscalPeriod->name}' is closed"
            );
        }

        if ($entry->fiscalPeriod->is_locked) {
            throw new PeriodClosedException(
                "Fiscal period '{$entry->fiscalPeriod->name}' is locked"
            );
        }

        return true;
    }

    /**
     * Validate that no parent accounts are being posted to
     *
     * @throws ParentAccountPostingException
     */
    public function validateParentAccounts(JournalEntry $entry): bool
    {
        if (config('laravel_accounting.allow_posting_on_parents', false)) {
            return true;
        }

        $entry->load('items.account.children');
        foreach ($entry->items as $item) {
            if ($item->account && $item->account->children()->exists()) {
                throw new ParentAccountPostingException(
                    "Cannot post to parent account '{$item->account->name}' (Code: {$item->account->code}). "
                    .'This account has child accounts and posting is not allowed on parent accounts.'
                );
            }
        }

        return true;
    }

    /**
     * Post a journal entry
     *
     * @throws Throwable
     */
    public function post(JournalEntry $entry, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($entry, $userId) {
            if ($entry->is_posted) {
                throw new DuplicatePostingException("Journal entry {$entry->id} is already posted");
            }

            $this->validateBalance($entry);
            $this->validatePeriod($entry);
            $this->validateParentAccounts($entry);

            $entry->update([
                'is_posted' => true,
                'posted_at' => now(),
                'posted_by_user_id' => $userId,
            ]);

            event(new JournalEntryPosted($entry, $userId));

            return true;
        });
    }

    /**
     * Reverse a journal entry
     *
     * @throws Throwable
     */
    public function reverse(JournalEntry $entry, ?string $reason = null, ?int $userId = null): JournalEntry
    {
        return DB::transaction(function () use ($entry, $reason, $userId) {
            if (! $entry->is_posted) {
                throw new InvalidArgumentException('Cannot reverse an unposted journal entry');
            }

            if ($entry->is_reversed) {
                throw new InvalidArgumentException('Journal entry has already been reversed');
            }

            if ($entry->is_voided) {
                throw new InvalidArgumentException('Cannot reverse a voided journal entry');
            }

            $reversalEntry = JournalEntry::create([
                'transaction_date' => now(),
                'description' => "Reversal: {$entry->description}".($reason ? " - {$reason}" : ''),
                'reference' => "REV-{$entry->reference}",
                'currency_code' => $entry->currency_code,
                'fiscal_period_id' => $this->findFiscalPeriod(now())?->id,
                'created_by_user_id' => $userId,
                'posted_by_user_id' => $userId,
                'is_posted' => true,
                'posted_at' => now(),
                'reversal_of_id' => $entry->id,
                'metadata' => $reason ? ['reversal_reason' => $reason] : [],
            ]);

            foreach ($entry->items as $item) {
                JournalEntryItem::create([
                    'journal_entry_id' => $reversalEntry->id,
                    'account_id' => $item->account_id,
                    'description' => $item->description,
                    'debit_amount' => $item->credit_amount, // Swaped
                    'credit_amount' => $item->debit_amount, // Swaped
                    'currency_code' => $item->currency_code,
                ]);
            }

            $entry->update([
                'is_reversed' => true,
            ]);

            $reversalEntry->load('items');

            event(new JournalEntryReversed($entry, $reversalEntry, $reason, $userId));

            return $reversalEntry;
        });
    }

    /**
     * Void a journal entry
     *
     * @throws Throwable
     */
    public function void(JournalEntry $entry, ?string $reason = null, int|string|null $userId = null): bool
    {
        return DB::transaction(static function () use ($entry, $reason, $userId) {
            if ($entry->is_voided) {
                throw new InvalidArgumentException('Journal entry is already voided');
            }

            if ($entry->is_posted) {
                throw new InvalidArgumentException('Cannot void a posted entry. Use reverse() instead.');
            }

            $entry->update([
                'is_voided' => true,
                'voided_at' => now(),
                'voided_by_user_id' => $userId,
                'metadata' => array_merge($entry->metadata ?? [], ['void_reason' => $reason]),
            ]);

            event(new JournalEntryVoided($entry, $reason, $userId));

            return true;
        });
    }

    /**
     * Get journal entries for a specific period
     */
    public function getEntriesForPeriod(FiscalPeriod $period, bool $postedOnly = true): Collection|array
    {
        $query = JournalEntry::where('fiscal_period_id', $period->id);

        if ($postedOnly) {
            $query->where('is_posted', true);
        }

        return $query->with(['items.account'])->get();
    }

    /**
     * Get paginated transactions with aggregated account information
     * Optimized for large datasets using correlated subqueries instead of GROUP BY
     *
     * @param  array  $filters  Optional filters: ['status' => 'posted|draft|voided', 'date_from' => Carbon, 'date_to' => Carbon, 'account_id' => int, 'reference' => string, 'fiscal_period_id' => int]
     * @param  int  $perPage  Number of items per page
     */
    public function getTransactions(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $prefix = config('laravel_accounting.table_prefix', 'la');
        $driver = DB::connection()->getDriverName();

        $accountsAggregation = $this->getAccountsAggregationQuery($driver, $prefix);
        $debitSum = "(SELECT COALESCE(SUM(debit_amount), 0) FROM {$prefix}_journal_entry_items WHERE journal_entry_id = je.id)";
        $creditSum = "(SELECT COALESCE(SUM(credit_amount), 0) FROM {$prefix}_journal_entry_items WHERE journal_entry_id = je.id)";

        $query = DB::table("{$prefix}_journal_entries as je")
            ->select(
                'je.id',
                'je.transaction_number',
                'je.transaction_date',
                'je.description',
                'je.reference',
                'je.postable_type',
                'je.postable_id',
                'je.is_posted',
                'je.is_voided',
                'je.is_reversed',
                'je.posted_at',
                'je.currency_code',
                'je.fiscal_period_id',
                DB::raw("({$accountsAggregation}) as accounts"),
                DB::raw("{$debitSum} as total_debit"),
                DB::raw("{$creditSum} as total_credit")
            );

        $this->applyTransactionFilters($query, $filters);

        return $query
            ->orderBy('je.transaction_date', 'desc')
            ->orderBy('je.id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get database-specific accounts aggregation query
     */
    protected function getAccountsAggregationQuery(string $driver, string $prefix): string
    {
        return match ($driver) {
            'mysql', 'mariadb' => "SELECT GROUP_CONCAT(DISTINCT acc.name ORDER BY acc.name SEPARATOR ', ')
                FROM {$prefix}_journal_entry_items jei
                INNER JOIN {$prefix}_accounts acc ON jei.account_id = acc.id
                WHERE jei.journal_entry_id = je.id",
            'pgsql' => "SELECT STRING_AGG(DISTINCT acc.name, ', ' ORDER BY acc.name)
                FROM {$prefix}_journal_entry_items jei
                INNER JOIN {$prefix}_accounts acc ON jei.account_id = acc.id
                WHERE jei.journal_entry_id = je.id",
            'sqlite' => "SELECT GROUP_CONCAT(DISTINCT acc.name, ', ')
                FROM {$prefix}_journal_entry_items jei
                INNER JOIN {$prefix}_accounts acc ON jei.account_id = acc.id
                WHERE jei.journal_entry_id = je.id",
            default => "SELECT GROUP_CONCAT(DISTINCT acc.name, ', ')
                FROM {$prefix}_journal_entry_items jei
                INNER JOIN {$prefix}_accounts acc ON jei.account_id = acc.id
                WHERE jei.journal_entry_id = je.id",
        };
    }

    /**
     * Apply filters to transaction query
     */
    protected function applyTransactionFilters($query, array $filters): void
    {
        if (isset($filters['status'])) {
            match ($filters['status']) {
                'posted' => $query->where('je.is_posted', true)->where('je.is_voided', false),
                'unposted', 'draft' => $query->where('je.is_posted', false)->where('je.is_voided', false),
                'voided' => $query->where('je.is_voided', true),
                'reversed' => $query->where('je.is_reversed', true),
                default => null,
            };
        }

        if (isset($filters['start_date'])) {
            $query->where('je.transaction_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('je.transaction_date', '<=', $filters['end_date']);
        }

        if (isset($filters['fiscal_period_id'])) {
            $query->where('je.fiscal_period_id', $filters['fiscal_period_id']);
        }

        if (isset($filters['account_id'])) {
            $query->whereExists(function ($subQuery) use ($filters) {
                $prefix = config('laravel_accounting.table_prefix', 'la');
                $subQuery->select(DB::raw(1))
                    ->from("{$prefix}_journal_entry_items as jei_filter")
                    ->whereColumn('jei_filter.journal_entry_id', 'je.id')
                    ->where('jei_filter.account_id', $filters['account_id']);
            });
        }

        if (isset($filters['reference']) && ! empty($filters['reference'])) {
            $query->where('je.reference', 'like', '%'.$filters['reference'].'%');
        }

        if (isset($filters['search']) && ! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('je.description', 'like', '%'.$filters['search'].'%')
                    ->orWhere('je.reference', 'like', '%'.$filters['search'].'%')
                    ->orWhere('je.transaction_number', 'like', '%'.$filters['search'].'%');
            });
        }
    }

    /**
     * Validate voucher data
     *
     * @throws InvalidVoucherException
     * @throws JournalNotBalancedException
     */
    public function validateVoucher(VoucherData $voucher): void
    {
        if (empty($voucher->entries)) {
            throw new InvalidVoucherException('Voucher must have at least one entry');
        }

        if (count($voucher->entries) < 2) {
            throw new InvalidVoucherException('Voucher must have at least two entries (debit and credit)');
        }

        $totalAmount = array_reduce($voucher->entries, static function ($carry, $entry) {
            return $carry + ($entry->type === EntryType::CREDIT ? $entry->amount : -$entry->amount);
        }, 0);

        if (abs($totalAmount) > 0.01) {
            throw new JournalNotBalancedException('Voucher entries are not balanced');
        }

    }

    /**
     * Find the fiscal period for a given date
     */
    protected function findFiscalPeriod(Carbon $date): ?FiscalPeriod
    {
        return FiscalPeriod::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }
}
