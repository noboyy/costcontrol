<?php

namespace Database\Seeders;

use App\Models\CostCategory;
use App\Models\CostGroup;
use App\Models\CostType;
use App\Models\IncomeCategory;
use App\Models\IncomeType;
use Illuminate\Database\Seeder;

/**
 * Master data global (id_perusahaan = null) yang terlihat oleh SUPER ADMIN.
 * Idempotent per kode, aman dijalankan berulang.
 */
class GlobalMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->costGroups();
        $this->costCategories();
        $this->costTypes();
        $this->incomeCategories();
        $this->incomeTypes();
    }

    private function costGroups(): void
    {
        $groups = [
            ['kode' => 'po', 'nama' => 'PO — Pembelian', 'warna' => 'blue', 'urutan' => 1],
            ['kode' => 'lo', 'nama' => 'LO — Tenaga Kerja', 'warna' => 'green', 'urutan' => 2],
            ['kode' => 'oc', 'nama' => 'OC — Biaya Lain', 'warna' => 'yellow', 'urutan' => 3],
        ];

        foreach ($groups as $g) {
            CostGroup::updateOrCreate(
                ['id_perusahaan' => null, 'kode' => $g['kode']],
                array_merge($g, ['is_active' => true])
            );
        }
    }

    private function costCategories(): void
    {
        $categories = [
            ['kode' => 'material', 'nama' => 'Material', 'icon' => 'bi-bricks', 'warna' => 'blue', 'urutan' => 1, 'kelompok' => 'po'],
            ['kode' => 'labor', 'nama' => 'Tenaga Kerja', 'icon' => 'bi-people', 'warna' => 'green', 'urutan' => 2, 'kelompok' => 'lo'],
            ['kode' => 'equipment', 'nama' => 'Peralatan', 'icon' => 'bi-gear', 'warna' => 'yellow', 'urutan' => 3, 'kelompok' => 'po'],
            ['kode' => 'transport', 'nama' => 'Transport', 'icon' => 'bi-truck', 'warna' => 'blue', 'urutan' => 4, 'kelompok' => 'po'],
            ['kode' => 'overhead', 'nama' => 'Overhead / Tetap', 'icon' => 'bi-building', 'warna' => 'gray', 'urutan' => 5, 'kelompok' => 'oc'],
            ['kode' => 'service', 'nama' => 'Jasa', 'icon' => 'bi-tools', 'warna' => 'green', 'urutan' => 6, 'kelompok' => 'oc'],
            ['kode' => 'tax', 'nama' => 'Pajak', 'icon' => 'bi-receipt', 'warna' => 'red', 'urutan' => 7, 'kelompok' => 'oc'],
            ['kode' => 'other', 'nama' => 'Lainnya', 'icon' => 'bi-three-dots', 'warna' => 'gray', 'urutan' => 9, 'kelompok' => 'oc'],
        ];

        foreach ($categories as $cat) {
            CostCategory::updateOrCreate(
                ['id_perusahaan' => null, 'kode' => $cat['kode']],
                array_merge($cat, ['is_active' => true])
            );
        }
    }

    private function costTypes(): void
    {
        $types = [
            ['kode' => 'MAT', 'nama' => 'Material Bangunan', 'kategori' => 'material', 'default_unit' => 'Kilogram'],
            ['kode' => 'SEM', 'nama' => 'Semen', 'kategori' => 'material', 'default_unit' => 'Sak'],
            ['kode' => 'TKN', 'nama' => 'Tenaga Kerja', 'kategori' => 'labor', 'default_unit' => 'Orang'],
            ['kode' => 'TUK', 'nama' => 'Tukang', 'kategori' => 'labor', 'default_unit' => 'Orang'],
            ['kode' => 'SEK', 'nama' => 'Sewa Alat Berat', 'kategori' => 'equipment', 'default_unit' => 'Hari'],
            ['kode' => 'TRK', 'nama' => 'Truk/Transport', 'kategori' => 'transport', 'default_unit' => 'Unit'],
            ['kode' => 'ADM', 'nama' => 'Administrasi', 'kategori' => 'overhead', 'default_unit' => null],
            ['kode' => 'SRV', 'nama' => 'Jasa Lainnya', 'kategori' => 'service', 'default_unit' => null],
            ['kode' => 'PPN', 'nama' => 'Pajak (PPN)', 'kategori' => 'tax', 'default_unit' => null],
            ['kode' => 'OLS', 'nama' => 'Biaya Lain-lain', 'kategori' => 'other', 'default_unit' => null],
        ];

        foreach ($types as $type) {
            $exists = CostType::where('id_perusahaan', null)->where('kode', $type['kode'])->exists();
            if ($exists) {
                continue;
            }
            CostType::create($type);
        }
    }

    private function incomeCategories(): void
    {
        $categories = [
            ['kode' => 'sales', 'nama' => 'Penjualan', 'icon' => 'bi-cash-stack', 'warna' => 'green', 'urutan' => 1],
            ['kode' => 'contract', 'nama' => 'Kontrak / Termyn', 'icon' => 'bi-receipt', 'warna' => 'blue', 'urutan' => 2],
            ['kode' => 'payment', 'nama' => 'Pembayaran', 'icon' => 'bi-wallet2', 'warna' => 'blue', 'urutan' => 3],
            ['kode' => 'additional', 'nama' => 'Tambahan', 'icon' => 'bi-plus-circle', 'warna' => 'yellow', 'urutan' => 4],
            ['kode' => 'other', 'nama' => 'Lainnya', 'icon' => 'bi-three-dots', 'warna' => 'gray', 'urutan' => 9],
        ];

        foreach ($categories as $cat) {
            IncomeCategory::updateOrCreate(
                ['id_perusahaan' => null, 'kode' => $cat['kode']],
                array_merge($cat, ['is_active' => true])
            );
        }
    }

    private function incomeTypes(): void
    {
        $types = [
            ['kode' => 'DP', 'nama' => 'Down Payment (DP)', 'kategori' => 'payment', 'default_unit' => null],
            ['kode' => 'TER', 'nama' => 'Termin Pembayaran', 'kategori' => 'payment', 'default_unit' => null],
            ['kode' => 'PEL', 'nama' => 'Pelunasan', 'kategori' => 'payment', 'default_unit' => null],
            ['kode' => 'ADD', 'nama' => 'Addendum', 'kategori' => 'additional', 'default_unit' => null],
            ['kode' => 'VAR', 'nama' => 'Variasi/Perubahan', 'kategori' => 'additional', 'default_unit' => null],
            ['kode' => 'BON', 'nama' => 'Bonus/Insentif', 'kategori' => 'other', 'default_unit' => null],
            ['kode' => 'OLS', 'nama' => 'Pendapatan Lain-lain', 'kategori' => 'other', 'default_unit' => null],
        ];

        foreach ($types as $type) {
            $exists = IncomeType::where('id_perusahaan', null)->where('kode', $type['kode'])->exists();
            if ($exists) {
                continue;
            }
            IncomeType::create($type);
        }
    }
}