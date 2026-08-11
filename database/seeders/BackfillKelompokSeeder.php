<?php

namespace Database\Seeders;

use App\Models\CostCategory;
use Illuminate\Database\Seeder;

class BackfillKelompokSeeder extends Seeder
{
    private const MAP = [
        'po' => ['material', 'equipment', 'transport', 'bahan', 'bahan_baku', 'material_bangunan', 'perlengkapan'],
        'lo' => ['labor', 'sdm', 'tenaga', 'upah', 'subkon'],
        'oc' => ['overhead', 'service', 'tax', 'other', 'adm', 'administrasi', 'ops_harian', 'biaya_tetap', 'lainnya'],
    ];

    public function run(): void
    {
        foreach (self::MAP as $kelompok => $kodes) {
            CostCategory::whereIn('kode', $kodes)
                ->whereNull('kelompok')
                ->update(['kelompok' => $kelompok]);
        }
    }
}