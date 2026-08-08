<?php

namespace App\Services;

use App\Models\CostCategory;
use App\Models\CostType;
use App\Models\IncomeType;
use Illuminate\Support\Facades\DB;

class BusinessTemplateSeeder
{
    /**
     * Ensure UMKM master categories/types exist for a company.
     * Safe to call multiple times (idempotent by kode).
     */
    public function seedUmkm(?int $companyId): void
    {
        if (!$companyId) {
            return;
        }

        $now = now();

        $categories = [
            ['kode' => 'bahan_baku', 'nama' => 'Bahan Baku', 'icon' => 'bi-basket', 'warna' => 'blue', 'urutan' => 1],
            ['kode' => 'ops_harian', 'nama' => 'Operasional Harian', 'icon' => 'bi-cash-coin', 'warna' => 'yellow', 'urutan' => 2],
            ['kode' => 'biaya_tetap', 'nama' => 'Biaya Tetap', 'icon' => 'bi-building', 'warna' => 'gray', 'urutan' => 3],
            ['kode' => 'sdm', 'nama' => 'SDM & Insentif', 'icon' => 'bi-people', 'warna' => 'green', 'urutan' => 4],
            ['kode' => 'other', 'nama' => 'Lainnya', 'icon' => 'bi-three-dots', 'warna' => 'gray', 'urutan' => 9],
        ];

        foreach ($categories as $cat) {
            CostCategory::updateOrCreate(
                ['id_perusahaan' => $companyId, 'kode' => $cat['kode']],
                array_merge($cat, [
                    'id_perusahaan' => $companyId,
                    'is_active' => true,
                ])
            );
        }

        $costTypes = [
            ['kode' => 'BBK', 'nama' => 'Bahan Baku Utama', 'kategori' => 'bahan_baku', 'default_unit' => 'Kilogram'],
            ['kode' => 'BBP', 'nama' => 'Bahan Pendukung', 'kategori' => 'bahan_baku', 'default_unit' => 'Pieces'],
            ['kode' => 'KMS', 'nama' => 'Kemasan', 'kategori' => 'bahan_baku', 'default_unit' => 'Pack'],
            ['kode' => 'BSN', 'nama' => 'Bensin / Transport', 'kategori' => 'ops_harian', 'default_unit' => 'Liter'],
            ['kode' => 'GAS', 'nama' => 'Gas LPG', 'kategori' => 'ops_harian', 'default_unit' => 'Unit'],
            ['kode' => 'PRK', 'nama' => 'Parkir', 'kategori' => 'ops_harian', 'default_unit' => null],
            ['kode' => 'KCL', 'nama' => 'Kas Kecil / Lain-lain', 'kategori' => 'ops_harian', 'default_unit' => null],
            ['kode' => 'SEW', 'nama' => 'Sewa Tempat', 'kategori' => 'biaya_tetap', 'default_unit' => 'Bulan'],
            ['kode' => 'LST', 'nama' => 'Listrik', 'kategori' => 'biaya_tetap', 'default_unit' => 'Bulan'],
            ['kode' => 'NET', 'nama' => 'Internet / Kuota', 'kategori' => 'biaya_tetap', 'default_unit' => 'Bulan'],
            ['kode' => 'GJI', 'nama' => 'Gaji Pokok', 'kategori' => 'sdm', 'default_unit' => 'Orang'],
            ['kode' => 'INS', 'nama' => 'Insentif / Bonus', 'kategori' => 'sdm', 'default_unit' => 'Orang'],
            ['kode' => 'KUR', 'nama' => 'Upah Kurir', 'kategori' => 'sdm', 'default_unit' => 'Orang'],
        ];

        foreach ($costTypes as $type) {
            $exists = CostType::where('id_perusahaan', $companyId)
                ->where('kode', $type['kode'])
                ->exists();
            if ($exists) {
                continue;
            }
            CostType::create([
                'id_perusahaan' => $companyId,
                'kode' => $type['kode'],
                'nama' => $type['nama'],
                'kategori' => $type['kategori'],
                'default_unit' => $type['default_unit'],
            ]);
        }

        $incomeTypes = [
            ['kode' => 'OMZ', 'nama' => 'Omzet Harian', 'kategori' => 'sales', 'default_unit' => null],
            ['kode' => 'TRF', 'nama' => 'Transfer / QRIS', 'kategori' => 'sales', 'default_unit' => null],
            ['kode' => 'TUN', 'nama' => 'Tunai', 'kategori' => 'sales', 'default_unit' => null],
            ['kode' => 'OLA', 'nama' => 'Order Online', 'kategori' => 'sales', 'default_unit' => null],
            ['kode' => 'PLN', 'nama' => 'Pendapatan Lain-lain', 'kategori' => 'other', 'default_unit' => null],
        ];

        foreach ($incomeTypes as $type) {
            $exists = IncomeType::where('id_perusahaan', $companyId)
                ->where('kode', $type['kode'])
                ->exists();
            if ($exists) {
                continue;
            }
            IncomeType::create([
                'id_perusahaan' => $companyId,
                'kode' => $type['kode'],
                'nama' => $type['nama'],
                'kategori' => $type['kategori'],
                'default_unit' => $type['default_unit'],
            ]);
        }
    }
}
