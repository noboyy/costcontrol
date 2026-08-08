<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_entry', function (Blueprint $table) {
            $table->id('id_cost');
            $table->foreignId('id_perusahaan')->nullable()->constrained('perusahaan', 'id_perusahaan')->nullOnDelete();
            $table->foreignId('id_project')->constrained('project', 'id_project')->cascadeOnDelete();
            $table->foreignId('id_cost_type')->constrained('cost_type', 'id_cost_type')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('keterangan')->nullable();
            $table->decimal('qty', 10, 2)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('harga_satuan', 15, 2)->nullable();
            $table->decimal('total', 15, 2)->nullable();
            $table->text('catatan')->nullable();
            $table->string('file_bukti')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_entry');
    }
};
