<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project', function (Blueprint $table) {
            $table->id('id_project');
            $table->foreignId('id_perusahaan')->nullable()->constrained('perusahaan', 'id_perusahaan')->nullOnDelete();
            $table->foreignId('id_admin')->nullable()->constrained('pengguna', 'id_pengguna')->nullOnDelete();
            $table->string('nama_project');
            $table->string('client')->nullable();
            $table->string('lokasi')->nullable();
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->decimal('project_value', 15, 2)->nullable();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project');
    }
};
