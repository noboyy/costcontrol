<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_category', function (Blueprint $table) {
            $table->id('id_cost_category');
            $table->foreignId('id_perusahaan')->nullable()->constrained('perusahaan', 'id_perusahaan')->nullOnDelete();
            $table->string('kode', 50);
            $table->string('nama');
            $table->string('icon', 50)->nullable()->default('bi-folder');
            $table->string('warna', 20)->nullable()->default('gray');
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['id_perusahaan', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_category');
    }
};
