<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\Pengguna;
use App\Models\Perusahaan;
use App\Services\BusinessTemplateSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $company = Perusahaan::firstOrCreate(
            ['nama_perusahaan' => 'Sahla Journey'],
            [
                'alamat_lengkap' => 'Indonesia',
                'owner' => 'Owner Sahla Journey',
            ]
        );

        $pengguna = Pengguna::firstOrCreate(
            [
                'id_perusahaan' => $company->id_perusahaan,
                'nama_lengkap' => 'Super Admin',
            ],
            [
                'jabatan' => 'Owner',
                'no_hp' => null,
            ]
        );

        Akun::updateOrCreate(
            ['username' => 'admin'],
            [
                'id_pengguna' => $pengguna->id_pengguna,
                'role' => 'SUPER ADMIN',
                'email' => 'admin@sahlajourney.com',
                'password' => Hash::make('admin123'),
                'is_active' => '1',
                'change_password' => 0,
            ]
        );

        // Master data Travel Umroh (units + kategori/tipe biaya & pendapatan via template)
        $this->call(MasterDataSeeder::class);
        app(BusinessTemplateSeeder::class)->seedTravelUmroh($company->id_perusahaan);

        // Global master data (id_perusahaan = null) template Travel Umroh
        $this->call(GlobalMasterDataSeeder::class);

        $this->command?->info('Production seed done. Login: admin@sahlajourney.com / admin123');
    }
}
