<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurs_history', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('mata_uang', 8); // USD / SAR
            $table->string('sumber', 10)->default('manual'); // bi / manual
            $table->decimal('kurs', 12, 2); // IDR per 1 unit
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->index(['tanggal', 'mata_uang', 'sumber']);
        });

        Schema::table('cost_entry', function (Blueprint $table) {
            $table->string('mata_uang', 8)->default('IDR')->after('catatan');
            $table->decimal('amount_valas', 15, 2)->nullable()->after('mata_uang');
            $table->decimal('kurs', 12, 2)->nullable()->after('amount_valas');
            $table->string('kurs_sumber', 10)->nullable()->after('kurs');
        });
    }

    public function down(): void
    {
        Schema::table('cost_entry', function (Blueprint $table) {
            $table->dropColumn(['mata_uang', 'amount_valas', 'kurs', 'kurs_sumber']);
        });

        Schema::dropIfExists('kurs_history');
    }
};
