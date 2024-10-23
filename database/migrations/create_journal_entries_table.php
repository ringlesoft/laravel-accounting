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
            // id, date, reference_no, description, is_posted

            $table->timestamps();
        });
    }
};
