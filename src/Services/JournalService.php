<?php

namespace RingleSoft\LaravelAccounting\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\Enums\EntryType;
use RingleSoft\LaravelAccounting\Events\JournalEntryCreated;
use RingleSoft\LaravelAccounting\Events\JournalEntryPosted;
use RingleSoft\LaravelAccounting\Events\JournalEntryReversed;
use RingleSoft\LaravelAccounting\Events\JournalEntryVoided;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Exceptions\JournalNotBalancedException;
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
        return DB::transaction(function () use ($voucher, $userId) {
            $this->validateVoucher($voucher);

            $fiscalPeriod = $this->findFiscalPeriod($voucher->date);

            $journalData = [
                'transaction_date' => $voucher->date,
                'description' => $voucher->description,
                'reference' => $voucher->reference,
                'currency_code' => $voucher->currency_code ?? config('laravel_accounting.default_currency_code'),
                'fiscal_period_id' => $fiscalPeriod?->id,
                'posted_at' => now(),
                'posted_by_user_id' => $userId ?? auth()->id(),
                'created_by_user_id' => $userId ?? auth()->id(),
                'is_posted' => false,
                'metadata' => $voucher->metadata ?? [],
            ];
            if(isset($voucher->sourceModel) && ($keyName = $voucher->sourceModel->getKeyName()) && ($modalId = $voucher->sourceModel->$keyName)) {
                $journalData['postable_id'] = $modalId;
                $journalData['postable_type'] = get_class($voucher->sourceModel);

            }
            $journalEntry = JournalEntry::create($journalData);

            foreach ($voucher->entries as $entry) {
                JournalEntryItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $entry->accountId,
                    'description' => $entry->memo,
                    'debit_amount' => $entry->amount > 0 ? abs($entry->amount) : 0,
                    'credit_amount' => $entry->amount < 0 ? abs($entry->amount) : 0,
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
     * Post a journal entry
     *
     * @throws Throwable
     */
    public function post(JournalEntry $entry, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($entry, $userId) {
            if ($entry->is_posted) {
                return true;
            }

            $this->validateBalance($entry);
            $this->validatePeriod($entry);

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
            ]);

            foreach ($entry->items  as $item) {
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
        return DB::transaction(function () use ($entry, $reason, $userId) {
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
    public function getEntriesForPeriod(FiscalPeriod $period, bool $postedOnly = true)
    {
        $query = JournalEntry::where('fiscal_period_id', $period->id);

        if ($postedOnly) {
            $query->where('is_posted', true);
        }

        return $query->with(['items.account'])->get();
    }

    /**
     * Validate voucher data
     * @throws JournalNotBalancedException|InvalidArgumentException
     * @throws InvalidVoucherException
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
            return $carry + ($entry->type === EntryType::CREDIT ? $entry->amount : -$entry->amount );
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
