<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create((config('table_prefix', 'laravel_accounting')).'_ledger_entries_table', function (Blueprint $table) {
            $table->id();
            //id, account_id, date, description, debit_amount, credit_amount, balance

            $table->timestamps();
        });
    }
};
