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

        Schema::create($prefix.'_journal_entries', function (Blueprint $table) use ($prefix, $useUuid) {
            if ($useUuid) {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }

            $table->string('transaction_number', 50)->unique();
            $table->date('transaction_date');
            $table->timestamp('posted_at')->nullable();
            $table->text('description')->nullable();
            $table->string('reference', 100)->nullable();

            // Polymorphic relationship - postable_id must be string to support both UUID and integer
            $table->string('postable_id')->nullable();
            $table->string('postable_type')->nullable();

            $table->string('currency_code', 3);
            $table->decimal('exchange_rate', 20, 10)->default(1);

            if ($useUuid) {
                $table->uuid('fiscal_period_id');
                $table->uuid('created_by_user_id')->nullable();
                $table->uuid('posted_by_user_id')->nullable();
                $table->uuid('voided_by_user_id')->nullable();
                $table->uuid('reversal_of_id')->nullable();
                $table->uuid('tenant_id')->nullable();
            } else {
                $table->unsignedBigInteger('fiscal_period_id');
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->unsignedBigInteger('posted_by_user_id')->nullable();
                $table->unsignedBigInteger('voided_by_user_id')->nullable();
                $table->unsignedBigInteger('reversal_of_id')->nullable();
                $table->unsignedBigInteger('tenant_id')->nullable();
            }

            $table->boolean('is_posted')->default(false);
            $table->boolean('is_voided')->default(false);
            $table->boolean('is_reversed')->default(false);
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();

            $table->foreign('fiscal_period_id')
                ->references('id')
                ->on($prefix.'_fiscal_periods')
                ->onDelete('restrict');

            $table->foreign('reversal_of_id')
                ->references('id')
                ->on($prefix.'_journal_entries')
                ->onDelete('restrict');

            $table->index(['transaction_number']);
            $table->index(['transaction_date', 'is_posted']);
            $table->index(['fiscal_period_id']);
            $table->index(['postable_type', 'postable_id']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        $prefix = config('laravel_accounting.table_prefix', 'la');
        Schema::dropIfExists($prefix.'_journal_entries');
    }
};
