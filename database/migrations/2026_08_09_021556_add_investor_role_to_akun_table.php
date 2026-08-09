<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // role column is varchar(255) — no DDL change needed.
        // INVESTOR value supported at application level.
    }

    public function down(): void
    {
        //
    }
};
