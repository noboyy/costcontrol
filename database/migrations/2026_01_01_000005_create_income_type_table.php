<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_type', function (Blueprint $table) {
            $table->id('id_income_type');
            $table->foreignId('id_perusahaan')->nullable()->constrained('perusahaan', 'id_perusahaan')->nullOnDelete();
            $table->string('kode');
            $table->string('nama');
            $table->string('kategori')->nullable();
            $table->string('default_unit')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_type');
    }
};
