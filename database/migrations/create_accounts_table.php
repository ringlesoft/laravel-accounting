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

        Schema::create($prefix.'_accounts', function (Blueprint $table) use ($prefix, $useUuid) {
            if ($useUuid) {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }

            $table->string('code', 50);
            $table->string('name', 255);

            if ($useUuid) {
                $table->uuid('account_type_id');
            } else {
                $table->unsignedBigInteger('account_type_id');
            }

            $table->string('category', 100)->nullable();
            $table->string('sub_category', 100)->nullable();
            $table->string('report_section', 100)->nullable();

            if ($useUuid) {
                $table->uuid('parent_id')->nullable();
                $table->uuid('tenant_id')->nullable();
            } else {
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->unsignedBigInteger('tenant_id')->nullable();
            }

            $table->string('currency_code', 3)->default('USD');

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system_account')->default(false);
            $table->boolean('allows_manual_entries')->default(true);
            $table->timestamps();

            $table->foreign('account_type_id')
                ->references('id')
                ->on($prefix.'_account_types')
                ->onDelete('restrict');

            $table->foreign('parent_id')
                ->references('id')
                ->on($prefix.'_accounts')
                ->onDelete('restrict');

            $table->unique(['code', 'tenant_id']);
            $table->index(['code', 'is_active']);
            $table->index('parent_id');
            $table->index('tenant_id');
            $table->index('account_type_id');
            $table->index('currency_code');
        });
    }

    public function down(): void
    {
        $prefix = config('laravel_accounting.table_prefix', 'la');
        Schema::dropIfExists($prefix.'_accounts');
    }
};
