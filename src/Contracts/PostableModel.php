<?php

namespace RingleSoft\LaravelAccounting\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;

interface PostableModel
{

    /**
     * Get the amount of the transaction
     * @return float
     */
    public function getAmount(): float;


    public function getReference(): ?string;

    /**
     * @return VoucherData
     */
    public function toVoucher(): VoucherData;

    /**
     * @return bool
     */
    public function post(): bool;

    /**
     * @param PostableTemplate $template
     * @return bool
     */
    public function postWithTemplate(PostableTemplate $template): bool;

    public function getTemplate(): PostableTemplate;


    /**
     * @return bool
     */
    public function createJournal(): bool;

    /**
     * @return bool
     */
    public function postJournal(): bool;

    /**
     * @return MorphOne
     */
    public function journalEntry(): MorphOne;

    // isPosted
    public function isPosted(): bool;
}
