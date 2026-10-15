<?php

namespace RingleSoft\LaravelAccounting\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\Templates\AccountingTemplate;

/**
 * @phpstan-extends Model
 */
interface PostableModel
{
    /**
     * Get the amount of the transaction
     *
     * Named getPostableAmount() rather than the more obvious getAmount() to
     * avoid colliding with a method of that very generic name a host
     * application's own model is quite likely to already have, for an
     * unrelated purpose.
     */
    public function getPostableAmount(): float;

    public function getReference(): ?string;

    public function toVoucher(): VoucherData;

    public function post(): bool;

    public function postWithTemplate(AccountingTemplate $template): bool;

    public function getTemplate(): ?AccountingTemplate;

    public function createJournal(): bool;

    public function postJournal(): bool;

    public function journalEntry(): MorphOne;

    // isPosted
    public function isPosted(): bool;
}
