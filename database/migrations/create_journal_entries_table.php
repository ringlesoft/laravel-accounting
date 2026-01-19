<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create((config('table_prefix', 'laravel_accounting')).'_journal_entries_table', function (Blueprint $table) {
            $table->id();
            // - id
            // - transaction_number (unique, auto-generated)
            // - transaction_date
            // - posted_at (nullable, when it was posted)
            // - description/memo
            // - postable_id
            // - postable_type
            // - reference
            // - currency_code
            // - exchange_rate
            // - fiscal_period_id
            // - created_by_user_id
            // - posted_by_user_id
            // - is_voided
            // - voided_at
            // - voided_by_user_id
            // - created_at, updated_at
            // - is_posted (boolean),
            // - is_reversed (boolean),
            // - reversal_of_id, -- Self-join to original entry

            $table->timestamps();
        });
    }
};
