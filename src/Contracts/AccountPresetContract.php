<?php

namespace RingleSoft\LaravelAccounting\Contracts;

use BackedEnum;

/**
 * An account preset: a stable key, stored in an account's `preset` column,
 * that templates and services use to find the account to post to.
 *
 * The package's own presets live in Enums\AccountPreset. To add your own,
 * declare a string-backed enum implementing this interface - it works
 * anywhere a preset is accepted (template entries and constructor
 * parameters, resolveAccount(), ResolvesAccountPresets, AccountService):
 *
 *     enum MyPreset: string implements AccountPresetContract
 *     {
 *         case SECONDARY_BANK = 'SECONDARY_BANK';
 *     }
 *
 * Accounts are matched on the case's value alone, so a value must be unique
 * across every preset enum in use (don't reuse e.g. 'CASH'), and must be a
 * string: the `preset` column is a string, which some databases (Postgres)
 * won't compare against an integer.
 */
interface AccountPresetContract extends BackedEnum {}
