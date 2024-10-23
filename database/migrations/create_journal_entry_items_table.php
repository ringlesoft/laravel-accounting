<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create( (config('table_prefix', 'laravel_accounting')) . '_journal_entry_items_table', function (Blueprint $table) {
            $table->id();
            // id, journal_entry_id, account_id, description, debit_amount, credit_amount

           $table->timestamps();
        });
    }
};
