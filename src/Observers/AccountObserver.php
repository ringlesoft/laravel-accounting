<?php

namespace RingleSoft\LaravelAccounting\Observers;

use InvalidArgumentException;
use RingleSoft\LaravelAccounting\Exceptions\InvalidAccountTypeException;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Validators\AccountValidator;

class AccountObserver
{
    /**
     * Handle the Account "creating" event.
     * Validate account data before creation
     *
     * @throws InvalidArgumentException
     */
    public function creating(Account $account): bool
    {
        $accountData = $account->getAttributes();
        AccountValidator::validate($accountData);

        return true;
    }

    /**
     * Handle the Account "deleting" event.
     * Prevent deletion of accounts that have transactions
     */
    public function deleting(Account $account): bool
    {
        $transactionCount = $account->journalEntryItems()->count();

        if ($transactionCount > 0) {
            throw new InvalidAccountTypeException(
                "Cannot delete account '{$account->code} - {$account->name}'. ".
                "This account has {$transactionCount} transaction(s). ".
                'Accounts with transactions cannot be deleted. Consider deactivating it instead.'
            );
        }

        $childrenCount = $account->children()->count();

        if ($childrenCount > 0) {
            throw new InvalidAccountTypeException(
                "Cannot delete account '{$account->code} - {$account->name}'. ".
                "This account has {$childrenCount} child account(s). ".
                'Delete or reassign child accounts first.'
            );
        }

        return true;
    }

    /**
     * Handle the Account "updating" event.
     * Validate account type changes
     */
    public function updating(Account $account): bool
    {
        if ($account->isDirty('account_type_id')) {
            $transactionCount = $account->journalEntryItems()->count();

            if ($transactionCount > 0) {
                throw new InvalidAccountTypeException(
                    "Cannot change account type for account '{$account->code} - {$account->name}'. ".
                    "This account has {$transactionCount} transaction(s). ".
                    'Accounts with transactions cannot have their type changed.'
                );
            }
        }

        if ($account->isDirty('parent_id') && $account->parent_id) {
            if ($account->parent_id === $account->id) {
                throw new InvalidAccountTypeException(
                    'Account cannot be its own parent.'
                );
            }

            $parent = Account::find($account->parent_id);
            if ($parent && $parent->isDescendantOf($account)) {
                throw new InvalidAccountTypeException(
                    'Cannot create circular parent-child relationship. '.
                    'The selected parent is a descendant of this account.'
                );
            }
        }

        return true;
    }
}
