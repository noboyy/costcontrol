<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\IncomeCategory;
use App\Models\IncomeType;
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

        // Master data Travel Umroh (units + kategori/tipe via template)
        $this->call(MasterDataSeeder::class);
        app(BusinessTemplateSeeder::class)->seedTravelUmroh($company->id_perusahaan);

        // Global master data (id_perusahaan = null) template Travel Umroh
        $this->call(GlobalMasterDataSeeder::class);

        // Income categories — Travel Umroh
        $incomeCats = [
            ['kode' => 'pendaftaran', 'nama' => 'Pendaftaran Jemaah', 'icon' => 'bi-pencil-square', 'warna' => 'blue', 'urutan' => 1],
            ['kode' => 'pembayaran', 'nama' => 'Pembayaran Paket (DP/Cicilan/Pelunasan)', 'icon' => 'bi-cash-stack', 'warna' => 'green', 'urutan' => 2],
            ['kode' => 'paket', 'nama' => 'Paket', 'icon' => 'bi-box-seam', 'warna' => 'blue', 'urutan' => 3],
            ['kode' => 'tambahan', 'nama' => 'Upgrade & Ekstra', 'icon' => 'bi-plus-circle', 'warna' => 'yellow', 'urutan' => 4],
            ['kode' => 'komisi', 'nama' => 'Komisi & Jasa', 'icon' => 'bi-handshake', 'warna' => 'green', 'urutan' => 5],
            ['kode' => 'other', 'nama' => 'Lainnya', 'icon' => 'bi-three-dots', 'warna' => 'gray', 'urutan' => 9],
        ];
        foreach ($incomeCats as $c) {
            IncomeCategory::updateOrCreate(
                ['id_perusahaan' => $company->id_perusahaan, 'kode' => $c['kode']],
                array_merge($c, ['id_perusahaan' => $company->id_perusahaan, 'is_active' => true])
            );
        }

        // Sync orphan income type categories
        $used = IncomeType::where('id_perusahaan', $company->id_perusahaan)
            ->pluck('kategori')->filter()->map(fn ($k) => strtolower(trim($k)))->unique();
        $order = 10;
        foreach ($used as $kode) {
            IncomeCategory::firstOrCreate(
                ['id_perusahaan' => $company->id_perusahaan, 'kode' => $kode],
                [
                    'nama' => ucfirst(str_replace('_', ' ', $kode)),
                    'icon' => 'bi-folder',
                    'warna' => 'green',
                    'urutan' => $order++,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Production seed done. Login: admin@sahlajourney.com / admin123');
    }
}
