<?php

namespace RingleSoft\LaravelAccounting\Contracts;

use RingleSoft\LaravelAccounting\Enums\AccountPreset;
use RingleSoft\LaravelAccounting\Models\Account;

/**
 * Optional, additive contract for a PostableModel that wants to resolve its
 * own account for a given preset (e.g. based on a field or relation on the
 * model) instead of relying solely on the template's default preset lookup.
 * Not part of PostableModel itself so existing models are unaffected.
 */
interface ResolvesAccountPresets
{
    /**
     * Resolve a template entry's preset to a specific account for this model
     * instance. Return null to fall through to the template's default
     * resolution - an explicit resolveAccount() call on the template itself
     * still takes priority over this.
     */
    public function resolveAccountForPreset(AccountPreset $preset): Account|int|string|null;
}
