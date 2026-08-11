<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_group', function (Blueprint $table) {
            $table->id('id_cost_group');
            $table->foreignId('id_perusahaan')->nullable()->constrained('perusahaan', 'id_perusahaan')->nullOnDelete();
            $table->string('kode', 50);
            $table->string('nama');
            $table->string('warna', 20)->nullable()->default('gray');
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['id_perusahaan', 'kode']);
        });

        // Backfill: pastikan kelompok po/lo/oc ada utk semua perusahaan yg sudah memakainya
        $perusahaanIds = DB::table('cost_category')
            ->whereNotNull('kelompok')
            ->whereNotNull('id_perusahaan')
            ->distinct()
            ->pluck('id_perusahaan');

        $defaults = [
            ['kode' => 'po', 'nama' => 'PO — Pembelian', 'warna' => 'blue', 'urutan' => 1],
            ['kode' => 'lo', 'nama' => 'LO — Tenaga Kerja', 'warna' => 'green', 'urutan' => 2],
            ['kode' => 'oc', 'nama' => 'OC — Biaya Lain', 'warna' => 'yellow', 'urutan' => 3],
        ];

        foreach ($perusahaanIds as $pid) {
            foreach ($defaults as $g) {
                $exists = DB::table('cost_group')
                    ->where('id_perusahaan', $pid)
                    ->where('kode', $g['kode'])
                    ->exists();
                if (! $exists) {
                    DB::table('cost_group')->insert(array_merge($g, [
                        'id_perusahaan' => $pid,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_group');
    }
};