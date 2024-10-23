<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create((config('table_prefix', 'laravel_accounting')).'_accounts_table', function (Blueprint $table) {
            $table->id();
            //id, code, name, type, parent_id, description, is_active

            $table->timestamps();
        });
    }
};
