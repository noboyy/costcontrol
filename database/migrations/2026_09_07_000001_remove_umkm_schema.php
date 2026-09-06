<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: server prod sudah drop manual, fresh DB belum.
        foreach (['mode', 'budget_period', 'daily_budget', 'monthly_budget', 'business_type'] as $col) {
            if (Schema::hasColumn('project', $col)) {
                Schema::table('project', function ($table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }

        if (Schema::hasTable('daily_close')) {
            Schema::drop('daily_close');
        }
        if (Schema::hasTable('fixed_cost')) {
            Schema::drop('fixed_cost');
        }
    }

    public function down(): void
    {
        Schema::table('project', function ($table) {
            $table->string('mode', 20)->default('project')->nullable();
            $table->string('budget_period', 20)->default('total')->nullable();
            $table->decimal('daily_budget', 15, 2)->nullable();
            $table->decimal('monthly_budget', 15, 2)->nullable();
            $table->string('business_type', 50)->nullable();
        });
    }
};
