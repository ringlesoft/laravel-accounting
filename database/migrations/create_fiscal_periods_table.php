<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create((config('table_prefix', 'laravel_accounting')).'_fiscal_periods_table', function (Blueprint $table) {
            $table->id();
            //- id
            //- fiscal_year_id
            //- name (e.g., "Q1 2024", "January 2024")
            //- start_date
            //- end_date
            //- is_closed
            //- closed_at
            //- closed_by_user_id
            //- tenant_id
            $table->timestamps();
        });
    }
};
