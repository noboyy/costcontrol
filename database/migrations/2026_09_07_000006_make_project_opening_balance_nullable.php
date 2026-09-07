<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project', function (Blueprint $table) {
            $table->decimal('opening_balance', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('project', function (Blueprint $table) {
            $table->decimal('opening_balance', 15, 2)->default(0)->change();
        });
    }
};
