<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project', function (Blueprint $table) {
            $table->string('mode', 20)->default('project')->after('status');
            $table->string('budget_period', 20)->default('total')->after('mode');
            $table->decimal('daily_budget', 15, 2)->nullable()->after('budget_period');
            $table->decimal('monthly_budget', 15, 2)->nullable()->after('daily_budget');
            $table->string('business_type', 50)->nullable()->after('monthly_budget');
        });

        // Backfill existing rows
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("UPDATE project SET mode = 'project', budget_period = 'total' WHERE mode IS NULL OR mode = ''");
        } else {
            DB::table('project')->whereNull('mode')->orWhere('mode', '')->update([
                'mode' => 'project',
                'budget_period' => 'total',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('project', function (Blueprint $table) {
            $table->dropColumn(['mode', 'budget_period', 'daily_budget', 'monthly_budget', 'business_type']);
        });
    }
};
