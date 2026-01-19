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

        Schema::create($prefix.'_fiscal_years', function (Blueprint $table) use ($useUuid) {
            if ($useUuid) {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }

            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();

            if ($useUuid) {
                $table->uuid('closed_by_user_id')->nullable();
                $table->uuid('tenant_id')->nullable();
            } else {
                $table->unsignedBigInteger('closed_by_user_id')->nullable();
                $table->unsignedBigInteger('tenant_id')->nullable();
            }

            $table->timestamps();

            $table->index(['start_date', 'end_date', 'is_closed']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        $prefix = config('laravel_accounting.table_prefix', 'la');
        Schema::dropIfExists($prefix.'_fiscal_years');
    }
};
