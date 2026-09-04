<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;

        $units = [
            ['id_perusahaan' => $companyId, 'nama' => 'Orang', 'simbol' => 'org'],
            ['id_perusahaan' => $companyId, 'nama' => 'Trip / Keberangkatan', 'simbol' => 'trip'],
            ['id_perusahaan' => $companyId, 'nama' => 'Malam', 'simbol' => 'mlm'],
            ['id_perusahaan' => $companyId, 'nama' => 'Hari', 'simbol' => 'hr'],
            ['id_perusahaan' => $companyId, 'nama' => 'Paket', 'simbol' => 'pkt'],
            ['id_perusahaan' => $companyId, 'nama' => 'Unit', 'simbol' => 'unit'],
            ['id_perusahaan' => $companyId, 'nama' => 'Set', 'simbol' => 'set'],
            ['id_perusahaan' => $companyId, 'nama' => 'Bulan', 'simbol' => 'bln'],
            ['id_perusahaan' => $companyId, 'nama' => 'Lembar', 'simbol' => 'lbr'],
            ['id_perusahaan' => $companyId, 'nama' => 'Kilogram', 'simbol' => 'kg'],
        ];

        foreach ($units as $u) {
            Unit::updateOrCreate(
                ['id_perusahaan' => $u['id_perusahaan'], 'nama' => $u['nama']],
                $u
            );
        }

        echo "Master data (Travel Umroh) seeded successfully!\n";
        echo '- '.count($units)." units\n";
        echo "- kategori/tipe biaya & pendapatan via BusinessTemplateSeeder + ProductionSeeder\n";
    }
}
