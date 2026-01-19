<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create((config('table_prefix', 'laravel_accounting')).'_exchange_rates_table', function (Blueprint $table) {
            $table->id();
            //id, base_currency, target_currency, rate, date
            //- tenant_id

            $table->timestamps();
        });
    }
};
