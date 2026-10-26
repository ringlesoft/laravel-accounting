<?php

namespace RingleSoft\LaravelAccounting\Support;

use RingleSoft\LaravelAccounting\Exceptions\ChartStructureException;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Scopes\MultiTenantScope;

/**
 * Generates and checks account codes under a parent, following the seeded
 * chart's scheme:
 *
 *     10000 Current Assets          root
 *       10100 Cash & Equivalents    children of a root step by 100
 *         10101 Cash                deeper children step by 1
 *
 * A parent's children take codes of the parent's length, above the parent's
 * code and below its next sibling's (its range). Children of a root step by
 * 100 while that fits in the range, then by 1. When the range is full, a
 * 2-digit suffix is appended instead (10103 -> 1010301, 1010302, ...), and
 * a suffixed account's own children are suffixed too (101030101, ...).
 */
class AccountCodeGenerator
{
    public const ROOT_CHILD_STEP = 100;

    /**
     * @var array<int|string, array{code: string, parent_id: int|string|null}>
     */
    protected array $accounts = [];

    /**
     * Account ids by parent id ('' for root accounts).
     *
     * @var array<string, array<int|string>>
     */
    protected array $children = [];

    /**
     * The next free code for a new child of $parent in $tenantId.
     *
     * @throws ChartStructureException No code is left under the parent
     */
    public function next(Account $parent, int|string|null $tenantId): string
    {
        $this->load($parent, $tenantId);
        $parentCode = (string) $parent->code;
        $taken = array_flip(array_column($this->accounts, 'code'));
        $childCodes = $this->childCodes($parent->getKey());

        if ($this->usesRange($parent->getKey())) {
            $length = strlen($parentCode);
            $end = $this->rangeEnd($parent->getKey());
            $floor = (int) $parentCode;
            foreach ($childCodes as $code) {
                if (ctype_digit($code) && strlen($code) === $length) {
                    $floor = max($floor, (int) $code);
                }
            }

            $steps = $parent->parent_id === null ? [self::ROOT_CHILD_STEP, 1] : [1];
            foreach ($steps as $step) {
                for ($candidate = intdiv($floor, $step) * $step + $step; $candidate < $end; $candidate += $step) {
                    $code = str_pad((string) $candidate, $length, '0', STR_PAD_LEFT);
                    if (! isset($taken[$code])) {
                        return $code;
                    }
                }
            }
        }

        $last = 0;
        foreach ($childCodes as $code) {
            if ($this->isSuffixOf($code, $parentCode)) {
                $last = max($last, (int) substr($code, -2));
            }
        }
        for ($n = $last + 1; $n <= 99; $n++) {
            $code = $parentCode.sprintf('%02d', $n);
            if (! isset($taken[$code])) {
                return $code;
            }
        }

        throw new ChartStructureException("No free account code is left under '{$parent->name}' ({$parentCode}).");
    }

    /**
     * Whether $code is a valid code for a child of $parent: inside the
     * parent's range (and past the subtree of the sibling it would follow),
     * or the parent's code plus a 2-digit suffix. Uniqueness is checked
     * separately, by AccountValidator.
     *
     * @param  int|string|null  $ignoreId  The account being re-coded, if any
     */
    public function fits(string $code, Account $parent, int|string|null $tenantId, int|string|null $ignoreId = null): bool
    {
        $this->load($parent, $tenantId, $ignoreId);
        $parentCode = (string) $parent->code;

        if ($this->isSuffixOf($code, $parentCode)) {
            return true;
        }

        if (! $this->usesRange($parent->getKey()) || ! $this->sameLengthDigits($code, $parentCode)) {
            return false;
        }

        if ((int) $code <= (int) $parentCode || (int) $code >= $this->rangeEnd($parent->getKey())) {
            return false;
        }

        // Don't land inside the range of the sibling it would follow.
        $preceding = null;
        foreach ($this->childrenOf($parent->getKey()) as $id) {
            $sibling = $this->accounts[$id]['code'];
            if ($this->sameLengthDigits($sibling, $code) && $sibling < $code
                && ($preceding === null || $sibling > $this->accounts[$preceding]['code'])) {
                $preceding = $id;
            }
        }

        return $preceding === null || $this->maxSubtreeCode($preceding, strlen($code)) < (int) $code;
    }

    /**
     * Snapshot of the accounts that matter: the parent's tenant (structure)
     * and the new account's tenant (uniqueness), less $ignoreId.
     *
     * Read as plain rows: hydrating (and eager-loading) every account of
     * the tenant on each call would make bulk creation quadratic.
     */
    protected function load(Account $parent, int|string|null $tenantId, int|string|null $ignoreId = null): void
    {
        $this->accounts = Account::withoutGlobalScope(MultiTenantScope::class)
            ->where(static function ($query) use ($parent, $tenantId) {
                $query->where('tenant_id', $tenantId)->orWhere('tenant_id', $parent->tenant_id);
            })
            ->toBase()
            ->get(['id', 'code', 'parent_id'])
            ->mapWithKeys(static fn (object $account) => [$account->id => [
                'code' => (string) $account->code,
                'parent_id' => $account->parent_id,
            ]])
            ->all();

        // The parent itself, even if it isn't saved under either tenant.
        $this->accounts[$parent->getKey()] ??= ['code' => (string) $parent->code, 'parent_id' => $parent->parent_id];

        if ($ignoreId !== null) {
            unset($this->accounts[$ignoreId]);
        }

        $this->children = [];
        foreach ($this->accounts as $id => $account) {
            $this->children[(string) $account['parent_id']][] = $id;
        }
    }

    /**
     * @return array<int|string>
     */
    protected function childrenOf(int|string|null $parentId): array
    {
        return $this->children[(string) $parentId] ?? [];
    }

    /**
     * @return string[]
     */
    protected function childCodes(int|string $parentId): array
    {
        return array_map(fn ($id) => $this->accounts[$id]['code'], $this->childrenOf($parentId));
    }

    /**
     * Exclusive upper bound of the codes available to an account's children
     * of the same code length: its next sibling's code, else its parent's
     * bound (or, for a suffixed code, the end of the parent's suffix space;
     * for a root, the end of the code length).
     */
    protected function rangeEnd(int|string $id): int
    {
        $code = $this->accounts[$id]['code'];
        $parentId = $this->accounts[$id]['parent_id'];

        $next = null;
        foreach ($this->childrenOf($parentId) as $siblingId) {
            $sibling = $this->accounts[$siblingId]['code'];
            if ($siblingId != $id && $this->sameLengthDigits($sibling, $code) && $sibling > $code && ($next === null || $sibling < $next)) {
                $next = $sibling;
            }
        }
        if ($next !== null) {
            return (int) $next;
        }

        if ($parentId === null || ! isset($this->accounts[$parentId])) {
            return 10 ** strlen($code);
        }

        $parentCode = $this->accounts[$parentId]['code'];
        if (strlen($parentCode) === strlen($code)) {
            return $this->rangeEnd($parentId);
        }

        return (int) ($parentCode.str_repeat('9', strlen($code) - strlen($parentCode))) + 1;
    }

    /**
     * Highest same-length numeric code in an account's subtree, itself included.
     */
    protected function maxSubtreeCode(int|string $id, int $length): int
    {
        $max = (int) $this->accounts[$id]['code'];
        foreach ($this->childrenOf($id) as $childId) {
            $child = $this->accounts[$childId]['code'];
            if (ctype_digit($child) && strlen($child) === $length) {
                $max = max($max, $this->maxSubtreeCode($childId, $length));
            }
        }

        return $max;
    }

    /**
     * Whether an account's children take codes from its range: its code is
     * numeric and, unless it's a root, as long as its parent's. A suffixed
     * account's children are suffixed instead.
     */
    protected function usesRange(int|string $id): bool
    {
        $code = $this->accounts[$id]['code'];
        $parentId = $this->accounts[$id]['parent_id'];

        if (! ctype_digit($code)) {
            return false;
        }

        return $parentId === null || ! isset($this->accounts[$parentId])
            || strlen($this->accounts[$parentId]['code']) === strlen($code);
    }

    protected function isSuffixOf(string $code, string $parentCode): bool
    {
        return strlen($code) === strlen($parentCode) + 2
            && str_starts_with($code, $parentCode)
            && ctype_digit(substr($code, -2))
            && substr($code, -2) !== '00';
    }

    protected function sameLengthDigits(string $a, string $b): bool
    {
        return ctype_digit($a) && strlen($a) === strlen($b);
    }
}
