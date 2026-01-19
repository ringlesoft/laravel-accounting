<?php

namespace RingleSoft\LaravelAccounting\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use RingleSoft\LaravelAccounting\DTO\VoucherData;

interface PostableModel
{
    public function toVoucher(): VoucherData;
    public function post(): bool;
    public function createJournal(): bool;
    public function postJournal(): bool;

    public function journalEntry(): MorphOne;

}
