<?php

namespace RingleSoft\LaravelAccounting\Observers;

use InvalidArgumentException;
use RingleSoft\LaravelAccounting\Exceptions\ChartStructureException;
use RingleSoft\LaravelAccounting\Exceptions\InvalidAccountTypeException;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Support\AccountCodeGenerator;
use RingleSoft\LaravelAccounting\Support\ChartStructure;
use RingleSoft\LaravelAccounting\Support\PresetDefinitions;
use RingleSoft\LaravelAccounting\Validators\AccountValidator;

class AccountObserver
{
    /**
     * Handle the Account "creating" event.
     * Enforce the chart structure, generate a missing code under the
     * parent, then validate account data before creation
     *
     * @throws InvalidArgumentException|ChartStructureException
     */
    public function creating(Account $account): bool
    {
        $parent = $account->parent_id ? Account::find($account->parent_id) : null;

        // Found through the tenant scope, so another tenant's account (or a
        // missing one) can't be used as the parent.
        if ($account->parent_id && ! $parent && (ChartStructure::enforced() || blank($account->code))) {
            throw new ChartStructureException("Parent account '{$account->parent_id}' was not found.");
        }

        if (ChartStructure::enforced()) {
            if (! $account->parent_id) {
                throw new ChartStructureException(
                    "Account '{$account->name}' needs a parent: root accounts are defined by the package's chart only."
                );
            }

            if (PresetDefinitions::isLocked($account->preset)) {
                throw new ChartStructureException("The {$account->preset} root account is created by the package's chart only.");
            }
        }

        if ($parent && blank($account->code)) {
            $account->code = app(AccountCodeGenerator::class)->next($parent, $account->tenant_id);
        } elseif ($parent && ChartStructure::enforced()) {
            $this->assertCodeFits($account, $parent);
        }

        AccountValidator::validate($account->getAttributes());

        return true;
    }

    /**
     * Handle the Account "deleting" event.
     * Prevent deletion of accounts that have transactions
     */
    public function deleting(Account $account): bool
    {
        if (ChartStructure::enforced() && PresetDefinitions::isLocked($account->getOriginal('preset'))) {
            throw new ChartStructureException("'{$account->name}' is a root account of the chart and can't be deleted.");
        }

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
        if (ChartStructure::enforced()) {
            $this->assertStructureKept($account);
        }

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

    /**
     * @throws ChartStructureException
     */
    protected function assertStructureKept(Account $account): void
    {
        // A touch() only dirties updated_at, which doesn't change the chart.
        $changes = array_diff(array_keys($account->getDirty()), [$account->getUpdatedAtColumn()]);
        if (! $changes) {
            return;
        }

        if (PresetDefinitions::isLocked($account->getOriginal('preset'))) {
            throw new ChartStructureException("'{$account->name}' is a root account of the chart and can't be changed.");
        }

        if ($account->isDirty('preset') && PresetDefinitions::isLocked($account->preset)) {
            throw new ChartStructureException("The {$account->preset} preset belongs to a root account of the chart.");
        }

        if ($account->isDirty('parent_id') && ! $account->parent_id) {
            throw new ChartStructureException("'{$account->name}' needs a parent: root accounts are defined by the package's chart only.");
        }

        if ($account->isDirty(['code', 'parent_id'])) {
            $this->assertCodeFits($account, Account::find($account->parent_id));
        }
    }

    /**
     * @throws ChartStructureException
     */
    protected function assertCodeFits(Account $account, ?Account $parent): void
    {
        if (! $parent) {
            return; // A missing parent is reported by AccountValidator's exists rule.
        }

        if (! app(AccountCodeGenerator::class)->fits((string) $account->code, $parent, $account->tenant_id, $account->getKey())) {
            throw new ChartStructureException(
                "Code '{$account->code}' doesn't fit under '{$parent->name}' ({$parent->code}). Omit the code to have one ".
                'generated, or use one between the parent\'s code and its next sibling\'s (or the parent\'s code plus a 2-digit suffix).'
            );
        }
    }
}
