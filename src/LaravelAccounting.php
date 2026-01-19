<?php

namespace RingleSoft\LaravelAccounting;

use Illuminate\Support\Collection;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\DTO\VoucherData;
use RingleSoft\LaravelAccounting\Models\Account;
use RingleSoft\LaravelAccounting\Models\JournalEntry;

class LaravelAccounting
{
    //- Journal creation and posting
    //- Reversals and adjustments
    //- Period validation and locking
    //- Balance and ledger queries
    //- Report data generation


    public function chartsOfAccounts(): Collection
    {
        return Account::all();
    }

    public function post(PostableModel $postable)
    {

    }

    public function postRaw(VoucherData $voucher)
    {

    }

    public function reverse(JournalEntry $journalEntry)
    {

    }

    public function adjust()
    {

    }

    public function validate(PostableModel $postable)
    {

    }

    public function reports()
    {

    }

    // period()->current()
    // coa(); // chart of accounts
    // balance($account)->asOf($date)
    //templates()->register(InvoiceTemplate::class);
}
