<?php

namespace RingleSoft\LaravelAccounting\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;

interface PostableModel
{
    /**
     * Get the amount of the transaction
     */
    public function getAmount(): float;

    public function getReference(): ?string;

    public function toVoucher(): VoucherData;

    public function post(): bool;

    public function postWithTemplate(PostableTemplate $template): bool;

    public function getTemplate(): ?PostableTemplate;

    public function createJournal(): bool;

    public function postJournal(): bool;

    public function journalEntry(): MorphOne;

    // isPosted
    public function isPosted(): bool;
}
