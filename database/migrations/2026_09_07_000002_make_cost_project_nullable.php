<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cost_entry', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->dropForeign(['id_project']);
            }
            $table->unsignedBigInteger('id_project')->nullable()->change();
            $table->foreign('id_project')->references('id_project')->on('project')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cost_entry', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->dropForeign(['id_project']);
            }
            $table->unsignedBigInteger('id_project')->nullable(false)->change();
            $table->foreign('id_project')->references('id_project')->on('project')->cascadeOnDelete();
        });
    }
};
