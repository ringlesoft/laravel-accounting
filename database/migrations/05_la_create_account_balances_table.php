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

        Schema::create($prefix.'_account_balances', function (Blueprint $table) use ($prefix, $useUuid) {
            if ($useUuid) {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }

            if ($useUuid) {
                $table->uuid('account_id');
                $table->uuid('fiscal_period_id');
                $table->uuid('tenant_id')->nullable();
            } else {
                $table->unsignedBigInteger('account_id');
                $table->unsignedBigInteger('fiscal_period_id');
                $table->unsignedBigInteger('tenant_id')->nullable();
            }

            $table->decimal('opening_balance', 20, 2)->default(0);
            $table->decimal('debit_total', 20, 2)->default(0);
            $table->decimal('credit_total', 20, 2)->default(0);
            $table->decimal('closing_balance', 20, 2)->default(0);
            $table->string('currency_code', 3);
            $table->timestamps();

            $table->foreign('account_id')
                ->references('id')
                ->on($prefix.'_accounts')
                ->onDelete('cascade');

            $table->foreign('fiscal_period_id')
                ->references('id')
                ->on($prefix.'_fiscal_periods')
                ->onDelete('cascade');

            $table->unique(['account_id', 'fiscal_period_id', 'tenant_id']);
            $table->index('account_id');
            $table->index('fiscal_period_id');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        $prefix = config('laravel_accounting.table_prefix', 'la');
        Schema::dropIfExists($prefix.'_account_balances');
    }
};
