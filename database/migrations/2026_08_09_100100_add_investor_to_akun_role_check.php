<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE akun DROP CONSTRAINT akun_role_check');
        DB::statement("ALTER TABLE akun ADD CONSTRAINT akun_role_check CHECK (role IN ('SUPER ADMIN', 'ADMIN', 'USER', 'INVESTOR'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE akun DROP CONSTRAINT akun_role_check');
        DB::statement("ALTER TABLE akun ADD CONSTRAINT akun_role_check CHECK (role IN ('SUPER ADMIN', 'ADMIN', 'USER'))");
    }
};