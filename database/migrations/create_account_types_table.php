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

        Schema::create($prefix.'_account_types', function (Blueprint $table) use ($useUuid) {
            if ($useUuid) {
                $table->uuid('id')->primary();
            } else {
                $table->id();
            }

            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->string('report_section', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['code', 'is_active']);
        });
    }

    public function down(): void
    {
        $prefix = config('laravel_accounting.table_prefix', 'la');
        Schema::dropIfExists($prefix.'_account_types');
    }
};
