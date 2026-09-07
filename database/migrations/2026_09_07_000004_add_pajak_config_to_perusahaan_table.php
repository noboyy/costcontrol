<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perusahaan', function (Blueprint $table) {
            $table->string('mode_pajak', 20)->default('final_omzet')->after('opening_balance');
            $table->decimal('tarif_pph_omzet', 5, 2)->default(0.50)->after('mode_pajak');
            $table->decimal('tarif_pph_laba', 5, 2)->default(22.00)->after('tarif_pph_omzet');
            $table->boolean('pajak_aktif')->default(true)->after('tarif_pph_laba');
        });
    }

    public function down(): void
    {
        Schema::table('perusahaan', function (Blueprint $table) {
            $table->dropColumn(['mode_pajak', 'tarif_pph_omzet', 'tarif_pph_laba', 'pajak_aktif']);
        });
    }
};
