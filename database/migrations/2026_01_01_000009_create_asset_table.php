<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset', function (Blueprint $table) {
            $table->id('id_asset');
            $table->foreignId('id_perusahaan')->nullable()->constrained('perusahaan', 'id_perusahaan')->nullOnDelete();
            $table->string('nama_asset');
            $table->decimal('nilai_asset', 15, 2)->nullable();
            $table->text('keterangan')->nullable();
            $table->string('gambar')->nullable();
            $table->enum('status', ['Ada', 'Dijual'])->default('Ada');
            $table->text('alasan_jual')->nullable();
            $table->decimal('nilai_jual', 15, 2)->nullable();
            $table->date('tanggal_jual')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset');
    }
};
