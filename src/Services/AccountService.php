<?php

namespace RingleSoft\LaravelAccounting\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Enums\AccountTypeEnum;
use RingleSoft\LaravelAccounting\Exceptions\AccountNotFoundException;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\AccountType;
use Throwable;

class AccountService
{
    /**
     * Create a new account
     *
     * @throws InvalidArgumentException|Throwable Missing/invalid required field, or duplicate code
     */
    public function create(array $data): Account
    {
        // is_system_account is a protected flag that only the package's own seeders
        unset($data['is_system_account']);

        return DB::transaction(function () use ($data) {
            $this->validateAccountData($data);

            if (isset($data['parent_id'])) {
                $parent = Account::findOrFail($data['parent_id']);

                if (! $parent->allows_manual_entries) {
                    $data['allows_manual_entries'] = $data['allows_manual_entries'] ?? true;
                }
            }

            return Account::create(array_merge([
                'is_active' => true,
                'allows_manual_entries' => true,
                'currency_code' => config('laravel_accounting.default_currency_code'),
            ], $data));
        });
    }

    /**
     * Update an account
     *
     * @throws InvalidArgumentException|Throwable Attempting to change a system account's code
     */
    public function update(Account $account, array $data): Account
    {
        // See create() - is_system_account must never flip via this entry point.
        unset($data['is_system_account']);

        return DB::transaction(static function () use ($account, $data) {
            if ($account->is_system_account && isset($data['code'])) {
                throw new InvalidArgumentException('Cannot change code of system account');
            }

            $account->update($data);

            return $account->fresh();
        });
    }

    /**
     * Deactivate an account
     *
     * @throws InvalidArgumentException System account, or account already has transactions
     */
    public function deactivate(Account $account): bool
    {
        if ($account->is_system_account) {
            throw new InvalidArgumentException('Cannot deactivate system account');
        }

        if ($this->hasTransactions($account)) {
            throw new InvalidArgumentException('Cannot deactivate account with transactions. Consider marking as inactive instead.');
        }

        return $account->update(['is_active' => false]);
    }

    /**
     * Activate an account
     */
    public function activate(Account $account): bool
    {
        return $account->update(['is_active' => true]);
    }

    /**
     * Get account hierarchy
     */
    public function getHierarchy(?int $parentId = null): Collection
    {
        $accounts = Account::where('parent_id', $parentId)
            ->with(['accountType', 'children'])
            ->orderBy('code')
            ->get();

        return $accounts->map(function ($account) {
            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->accountType->name,
                'is_active' => $account->is_active,
                'allows_manual_entries' => $account->allows_manual_entries,
                'has_children' => $account->children->isNotEmpty(),
                'children' => $this->getHierarchy($account->id),
            ];
        });
    }

    /**
     * Get accounts by type
     */
    public function getByType(AccountTypeEnum $accountType): Collection
    {
        $typeId = AccountType::findByName($accountType->value)?->id;

        return Account::where('account_type_id', $typeId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    /**
     * Get accounts that allow manual entries
     */
    public function getPostableAccounts(): Collection
    {
        return Account::where('is_active', true)
            ->where('allows_manual_entries', true)
            ->with('accountType')
            ->orderBy('code')
            ->get();
    }

    public function all()
    {
        return Account::where('is_active', true)
            ->with('accountType')
            ->orderBy('code')
            ->get();
    }

    /**
     * Search accounts
     */
    public function search(string $query): Collection
    {
        return Account::where(function ($q) use ($query) {
            $q->where('code', 'like', "%{$query}%")
                ->orWhere('name', 'like', "%{$query}%");
        })
            ->where('is_active', true)
            ->with('accountType')
            ->orderBy('code')
            ->limit(50)
            ->get();
    }

    /**
     * Get account by code
     */
    public function findByCode(string $code): ?Account
    {
        return Account::where('code', $code)->first();
    }

    /**
     * Get account by preset
     */
    public function findByPreset(AccountPreset $preset): ?Account
    {
        return Account::where('preset', $preset->value)->first();
    }

    /**
     * Get the last level child accounts by preset
     *
     * @return Collection<Account>
     *
     * @throws AccountNotFoundException No account carries this preset at all
     */
    public function getLastChildrenByPreset(AccountPreset $preset): Collection
    {
        $account = Account::where('preset', $preset->value)->with('children.children')->first();
        if (! $account) {
            throw new AccountNotFoundException('Account not found for preset: '.$preset->value);
        }
        if ($account->children->count() > 0) {
            // TODO process level 2 of children
            return $account->children;
        }

        return collect([$account]);
    }

    /**
     * Get the accounts grouped under a "rollup" preset (e.g. Cash and Cash
     * Equivalents, Fixed Assets): the parent account's direct children when it
     * has any (the real transactional accounts), or the parent itself when it
     * doesn't (e.g. a flatter chart of accounts with no sub-accounts for that
     * preset). Returns an empty collection when no account carries the preset
     * at all, for callers (reports, balance aggregation) that should degrade
     * gracefully rather than fail - use getLastChildrenByPreset() instead when
     * the preset is expected to exist and its absence should be a hard error.
     */
    public function getAccountsForPresetGroup(AccountPreset $preset): Collection
    {
        $account = Account::where('preset', $preset->value)->with('children')->first();

        if (! $account) {
            return collect();
        }

        return $account->children->isNotEmpty() ? $account->children : collect([$account]);
    }

    /**
     * Check if account has transactions
     */
    public function hasTransactions(Account $account): bool
    {
        return $account->journalEntryItems()->exists();
    }

    /**
     * Get account with balance
     *
     * @throws ModelNotFoundException No account with this id
     */
    public function getWithBalance(int $accountId, ?\DateTimeInterface $asOfDate = null): array
    {
        $account = Account::with('accountType')->findOrFail($accountId);
        $balanceService = app(BalanceService::class);

        $balance = $balanceService->getAccountBalance($account, $asOfDate ? Carbon::parse($asOfDate) : null);

        return [
            'account' => $account,
            'balance' => $balance,
            'as_of_date' => $asOfDate ?? now(),
        ];
    }

    /**
     * Validate account data
     */
    protected function validateAccountData(array $data): void
    {
        if (empty($data['code'])) {
            throw new InvalidArgumentException('Account code is required');
        }

        if (empty($data['name'])) {
            throw new InvalidArgumentException('Account name is required');
        }

        if (empty($data['account_type_id'])) {
            throw new InvalidArgumentException('Account type is required');
        }

        $accountType = AccountType::find($data['account_type_id']);
        if (! $accountType) {
            throw new InvalidArgumentException('Invalid account type');
        }

        $existingAccount = Account::where('code', $data['code'])->first();
        if ($existingAccount) {
            throw new InvalidArgumentException("Account with code '{$data['code']}' already exists");
        }
    }

    /**
     * Get chart of accounts
     */
    public function getChartOfAccounts(): Collection
    {
        return Account::with(['accountType', 'parent'])
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get()
            ->map(function ($account) {
                return $this->buildAccountTree($account);
            });
    }

    /**
     * Build account tree recursively
     */
    protected function buildAccountTree(Account $account): array
    {
        $children = Account::with('accountType')
            ->where('parent_id', $account->id)
            ->orderBy('code')
            ->get()
            ->map(fn ($child) => $this->buildAccountTree($child));

        return [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'type' => $account->accountType->name,
            'category' => $account->category,
            'preset' => $account->preset,
            'normal_balance' => $account->normal_balance,
            'is_active' => $account->is_active,
            'is_system_account' => $account->is_system_account,
            'allows_manual_entries' => $account->allows_manual_entries,
            'children' => $children,
        ];
    }

    public function query()
    {
        return Account::query();
    }
}
