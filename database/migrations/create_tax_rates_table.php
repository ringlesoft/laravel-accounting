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

        Schema::create($prefix.'_tax_rates', function (Blueprint $table) use ($useUuid) {
            if ($useUuid) {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }

            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->decimal('rate', 10, 4);
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->text('description')->nullable();

            if ($useUuid) {
                $table->uuid('tenant_id')->nullable();
            } else {
                $table->unsignedBigInteger('tenant_id')->nullable();
            }

            $table->timestamps();

            $table->index(['code', 'is_active']);
            $table->index(['effective_from', 'effective_to']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        $prefix = config('laravel_accounting.table_prefix', 'la');
        Schema::dropIfExists($prefix.'_tax_rates');
    }
};
