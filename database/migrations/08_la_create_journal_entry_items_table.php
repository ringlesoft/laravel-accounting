<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('laravel_accounting.table_prefix', 'la');
        $useUuid = config('laravel_accounting.use_uuid', false);

        Schema::create($prefix.'_journal_entry_items', function (Blueprint $table) use ($prefix, $useUuid) {
            if ($useUuid) {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }

            if ($useUuid) {
                $table->uuid('journal_entry_id');
                $table->uuid('account_id');
            } else {
                $table->unsignedBigInteger('journal_entry_id');
                $table->unsignedBigInteger('account_id');
            }

            $table->text('description')->nullable();
            $table->decimal('debit_amount', 22, 4)->default(0);
            $table->decimal('credit_amount', 22, 4)->default(0);
            $table->string('currency_code', 3);
            $table->decimal('exchange_rate', 20, 10)->default(1);

            // Polymorphic relationship for dimensions (e.g., Customer, Vendor, Project, Branch)
            $table->string('dimension_id')->nullable();
            $table->string('dimension_type')->nullable();

            if ($useUuid) {
                $table->uuid('tenant_id')->nullable();
            } else {
                $table->unsignedBigInteger('tenant_id')->nullable();
            }

            $table->timestamps();

            $table->foreign('journal_entry_id')
                ->references('id')
                ->on($prefix.'_journal_entries')
                ->onDelete('cascade');

            $table->foreign('account_id')
                ->references('id')
                ->on($prefix.'_accounts')
                ->onDelete('restrict');

            // Note: Check constraints for debit_amount >= 0 and credit_amount >= 0
            // are enforced at the application level via validation
            // Database-level check constraints can be added via raw SQL if needed

            $table->index('journal_entry_id');
            $table->index('account_id');
            $table->index('tenant_id');
            $table->index(['dimension_type', 'dimension_id']);
        });
    }

    public function down(): void
    {
        $prefix = config('laravel_accounting.table_prefix', 'la');
        Schema::dropIfExists($prefix.'_journal_entry_items');
    }
};
