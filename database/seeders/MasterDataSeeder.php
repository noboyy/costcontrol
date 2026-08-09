<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;

        // Units
        $units = [
            ['id_perusahaan' => $companyId, 'nama' => 'Kilogram', 'simbol' => 'kg', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Ton', 'simbol' => 'ton', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Liter', 'simbol' => 'L', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Meter', 'simbol' => 'm', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Meter Persegi', 'simbol' => 'm²', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Meter Kubik', 'simbol' => 'm³', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Lembar', 'simbol' => 'lbr', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Batang', 'simbol' => 'btg', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Sak', 'simbol' => 'sak', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Drum', 'simbol' => 'drm', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Unit', 'simbol' => 'unit', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Roll', 'simbol' => 'roll', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Box', 'simbol' => 'box', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Set', 'simbol' => 'set', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Orang', 'simbol' => 'org', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Hari', 'simbol' => 'hr', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Bulan', 'simbol' => 'bln', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Lusin', 'simbol' => 'lsn', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Pack', 'simbol' => 'pack', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'nama' => 'Pieces', 'simbol' => 'pcs', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('unit_master')->insert($units);

        // Cost Types
        $costTypes = [
            ['id_perusahaan' => $companyId, 'kode' => 'MAT', 'nama' => 'Material Bangunan', 'kategori' => 'material', 'default_unit' => 'Kilogram', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'SEM', 'nama' => 'Semen', 'kategori' => 'material', 'default_unit' => 'Sak', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'PAS', 'nama' => 'Pasir', 'kategori' => 'material', 'default_unit' => 'Meter Kubik', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'BTU', 'nama' => 'Batu', 'kategori' => 'material', 'default_unit' => 'Meter Kubik', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'KAY', 'nama' => 'Kayu', 'kategori' => 'material', 'default_unit' => 'Meter Kubik', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'BSI', 'nama' => 'Besi/Baja', 'kategori' => 'material', 'default_unit' => 'Kilogram', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'CAT', 'nama' => 'Cat', 'kategori' => 'material', 'default_unit' => 'Liter', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'KRM', 'nama' => 'Keramik', 'kategori' => 'material', 'default_unit' => 'Meter Persegi', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'TKN', 'nama' => 'Tenaga Kerja', 'kategori' => 'labor', 'default_unit' => 'Orang', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'TUK', 'nama' => 'Tukang', 'kategori' => 'labor', 'default_unit' => 'Orang', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'MAN', 'nama' => 'Mandor', 'kategori' => 'labor', 'default_unit' => 'Orang', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'SEK', 'nama' => 'Sewa Alat Berat', 'kategori' => 'equipment', 'default_unit' => 'Hari', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'SAL', 'nama' => 'Sewa Alat Kecil', 'kategori' => 'equipment', 'default_unit' => 'Hari', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'TRK', 'nama' => 'Truk/Transport', 'kategori' => 'transport', 'default_unit' => 'Unit', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'EXP', 'nama' => 'Ekspedisi', 'kategori' => 'transport', 'default_unit' => 'Unit', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'ADM', 'nama' => 'Administrasi', 'kategori' => 'overhead', 'default_unit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'ATK', 'nama' => 'Alat Tulis Kantor', 'kategori' => 'overhead', 'default_unit' => 'Pack', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'TLK', 'nama' => 'Telekomunikasi', 'kategori' => 'overhead', 'default_unit' => 'Bulan', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'AKM', 'nama' => 'Akomodasi', 'kategori' => 'overhead', 'default_unit' => 'Hari', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'MKN', 'nama' => 'Konsumsi', 'kategori' => 'overhead', 'default_unit' => 'Orang', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'INS', 'nama' => 'Instalasi', 'kategori' => 'service', 'default_unit' => 'Unit', 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'SRV', 'nama' => 'Jasa Lainnya', 'kategori' => 'service', 'default_unit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'PPN', 'nama' => 'Pajak (PPN)', 'kategori' => 'tax', 'default_unit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'ASR', 'nama' => 'Asuransi', 'kategori' => 'overhead', 'default_unit' => null, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('cost_type')->insert($costTypes);

        // Income Types
        $incomeTypes = [
            ['id_perusahaan' => $companyId, 'kode' => 'DP', 'nama' => 'Down Payment (DP)', 'kategori' => 'payment', 'default_unit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'TER', 'nama' => 'Termin Pembayaran', 'kategori' => 'payment', 'default_unit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'PEL', 'nama' => 'Pelunasan', 'kategori' => 'payment', 'default_unit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'RET', 'nama' => 'Retensi', 'kategori' => 'payment', 'default_unit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'ADD', 'nama' => 'Addendum', 'kategori' => 'additional', 'default_unit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'VAR', 'nama' => 'Variasi/Perubahan', 'kategori' => 'additional', 'default_unit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'CLM', 'nama' => 'Klaim', 'kategori' => 'additional', 'default_unit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'PEN', 'nama' => 'Penyesuaian Harga', 'kategori' => 'adjustment', 'default_unit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'BON', 'nama' => 'Bonus/Insentif', 'kategori' => 'other', 'default_unit' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id_perusahaan' => $companyId, 'kode' => 'OLS', 'nama' => 'Pendapatan Lain-lain', 'kategori' => 'other', 'default_unit' => null, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('income_type')->insert($incomeTypes);

        echo "Master data seeded successfully!\n";
        echo '- '.count($units)." units\n";
        echo '- '.count($costTypes)." cost types\n";
        echo '- '.count($incomeTypes)." income types\n";
    }
}
