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

        // Note: This table is optional - the package primarily uses journal_entries
        // This can be used for tracking general business transactions before they're posted
        Schema::create($prefix.'_transactions', function (Blueprint $table) use ($useUuid) {
            if ($useUuid) {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }

            $table->date('transaction_date');
            $table->string('type', 50);
            $table->decimal('amount', 20, 2);
            $table->text('description')->nullable();
            $table->string('status', 50)->default('pending');
            $table->string('reference', 100)->nullable();

            if ($useUuid) {
                $table->uuid('tenant_id')->nullable();
            } else {
                $table->unsignedBigInteger('tenant_id')->nullable();
            }

            $table->timestamps();

            $table->index(['transaction_date', 'type']);
            $table->index('status');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        $prefix = config('laravel_accounting.table_prefix', 'la');
        Schema::dropIfExists($prefix.'_transactions');
    }
};
