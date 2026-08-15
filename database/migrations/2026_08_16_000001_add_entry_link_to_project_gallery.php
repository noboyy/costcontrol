<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_gallery', function (Blueprint $table) {
            $table->unsignedBigInteger('id_cost')->nullable()->after('id_project');
            $table->unsignedBigInteger('id_income')->nullable()->after('id_cost');

            $table->index('id_cost');
            $table->index('id_income');

            $table->foreign('id_cost')->references('id_cost')->on('cost_entry')->onDelete('cascade');
            $table->foreign('id_income')->references('id_income')->on('income_entry')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('project_gallery', function (Blueprint $table) {
            $table->dropForeign(['id_cost']);
            $table->dropForeign(['id_income']);
            $table->dropIndex(['id_cost']);
            $table->dropIndex(['id_income']);
            $table->dropColumn(['id_cost', 'id_income']);
        });
    }
};
