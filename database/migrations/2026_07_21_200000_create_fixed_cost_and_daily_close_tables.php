<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_cost', function (Blueprint $table) {
            $table->id('id_fixed_cost');
            $table->foreignId('id_perusahaan')->nullable()->constrained('perusahaan', 'id_perusahaan')->nullOnDelete();
            $table->foreignId('id_project')->constrained('project', 'id_project')->cascadeOnDelete();
            $table->foreignId('id_cost_type')->nullable()->constrained('cost_type', 'id_cost_type')->nullOnDelete();
            $table->string('nama');
            $table->decimal('amount_monthly', 15, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('daily_close', function (Blueprint $table) {
            $table->id('id_daily_close');
            $table->foreignId('id_perusahaan')->nullable()->constrained('perusahaan', 'id_perusahaan')->nullOnDelete();
            $table->foreignId('id_project')->constrained('project', 'id_project')->cascadeOnDelete();
            $table->date('tanggal');
            $table->decimal('total_income', 15, 2)->default(0);
            $table->decimal('total_cost_cash', 15, 2)->default(0);
            $table->decimal('total_cogs', 15, 2)->default(0);
            $table->decimal('total_ops', 15, 2)->default(0);
            $table->decimal('total_fixed_prorate', 15, 2)->default(0);
            $table->decimal('total_cost_economic', 15, 2)->default(0);
            $table->decimal('margin_cash', 15, 2)->default(0);
            $table->decimal('margin_economic', 15, 2)->default(0);
            $table->decimal('cogs_ratio', 8, 4)->nullable();
            $table->decimal('daily_budget', 15, 2)->nullable();
            $table->boolean('over_budget')->default(false);
            $table->boolean('leak_alert')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['id_project', 'tanggal']);
        });

        Schema::table('project', function (Blueprint $table) {
            $table->decimal('cogs_ratio_alert', 8, 4)->nullable()->after('business_type'); // e.g. 0.45 = 45%
            $table->boolean('lock_closed_days')->default(true)->after('cogs_ratio_alert');
        });
    }

    public function down(): void
    {
        Schema::table('project', function (Blueprint $table) {
            $table->dropColumn(['cogs_ratio_alert', 'lock_closed_days']);
        });
        Schema::dropIfExists('daily_close');
        Schema::dropIfExists('fixed_cost');
    }
};
