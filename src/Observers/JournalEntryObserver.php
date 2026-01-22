<?php

namespace RingleSoft\LaravelAccounting\Observers;

use RingleSoft\LaravelAccounting\Exceptions\DuplicatePostingException;
use RingleSoft\LaravelAccounting\Models\JournalEntry;

class JournalEntryObserver
{
    /**
     * Handle the JournalEntry "deleting" event.
     * Prevent deletion of posted journal entries
     */
    public function deleting(JournalEntry $journalEntry): bool
    {
        if ($journalEntry->is_posted) {
            throw new DuplicatePostingException(
                "Cannot delete posted journal entry #{$journalEntry->transaction_number}. " .
                "Posted entries must be reversed or voided instead."
            );
        }

        return true;
    }

    /**
     * Handle the JournalEntry "updating" event.
     * Prevent modification of posted journal entries
     */
    public function updating(JournalEntry $journalEntry): bool
    {
        if ($journalEntry->is_posted && $journalEntry->isDirty()) {
            $allowedFields = ['is_voided', 'voided_at', 'voided_by_user_id', 'is_reversed'];
            
            $dirtyFields = array_keys($journalEntry->getDirty());
            $unauthorizedChanges = array_diff($dirtyFields, $allowedFields);

            if (!empty($unauthorizedChanges)) {
                throw new DuplicatePostingException(
                    "Cannot modify posted journal entry #{$journalEntry->transaction_number}. " .
                    "Posted entries can only be reversed or voided. " .
                    "Attempted to modify: " . implode(', ', $unauthorizedChanges)
                );
            }
        }

        return true;
    }
}
