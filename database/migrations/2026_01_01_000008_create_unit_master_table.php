<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_master', function (Blueprint $table) {
            $table->id('id_unit');
            $table->foreignId('id_perusahaan')->nullable()->constrained('perusahaan', 'id_perusahaan')->nullOnDelete();
            $table->string('nama');
            $table->string('simbol')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_master');
    }
};
