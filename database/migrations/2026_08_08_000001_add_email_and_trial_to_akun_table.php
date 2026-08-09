<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akun', function (Blueprint $table) {
            $table->string('email')->nullable()->after('username');
            $table->timestamp('trial_ends_at')->nullable()->after('change_password');
        });

        // Backfill: existing users have no email, use username
        DB::table('akun')
            ->whereNull('email')
            ->whereNotNull('username')
            ->orderBy('id_akun')
            ->select('id_akun', 'username')
            ->get()
            ->each(function ($row) {
                if (filter_var($row->username, FILTER_VALIDATE_EMAIL)) {
                    DB::table('akun')->where('id_akun', $row->id_akun)->update(['email' => $row->username]);
                }
            });

        Schema::table('akun', function (Blueprint $table) {
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::table('akun', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn(['email', 'trial_ends_at']);
        });
    }
};
