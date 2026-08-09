<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_investor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_project');
            $table->unsignedBigInteger('id_akun');
            $table->timestamps();

            $table->unique('id_akun'); // 1 akun = 1 proyek
            $table->unique(['id_project', 'id_akun']);

            $table->foreign('id_project')
                ->references('id_project')
                ->on('project')
                ->cascadeOnDelete();

            $table->foreign('id_akun')
                ->references('id_akun')
                ->on('akun')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_investor');
    }
};
