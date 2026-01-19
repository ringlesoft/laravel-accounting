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
            // - id
            // - code (unique, indexable)
            // - name
            // - account_type_id (or enum: asset, liability, equity, revenue, expense)
            // - category
            // - sub_category
            // - report_section
            // - parent_id (for hierarchical COA)
            // - currency_id
            // - is_active
            // - is_system_account,
            // - allows_manual_entries (some accounts might be system-only)
            // - created_at, updated_at
            // - tenant_id

            $table->timestamps();
        });
    }
};
