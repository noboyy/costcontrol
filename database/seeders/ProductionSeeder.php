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
            ['nama_perusahaan' => 'Demo CostControl'],
            [
                'alamat_lengkap' => 'Indonesia',
                'owner' => 'Owner Demo',
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
                'email' => 'admin@costcontrol.id',
                'password' => Hash::make('admin123'),
                'is_active' => '1',
                'change_password' => 0,
            ]
        );

        // Project master + UMKM templates
        $this->call(MasterDataSeeder::class);
        app(BusinessTemplateSeeder::class)->seedUmkm($company->id_perusahaan);

        // Global master data (id_perusahaan = null) untuk SUPER ADMIN
        $this->call(GlobalMasterDataSeeder::class);

        // Income categories defaults
        $incomeCats = [
            ['kode' => 'sales', 'nama' => 'Penjualan', 'icon' => 'bi-cash-stack', 'warna' => 'green', 'urutan' => 1],
            ['kode' => 'contract', 'nama' => 'Kontrak / Termyn', 'icon' => 'bi-receipt', 'warna' => 'blue', 'urutan' => 2],
            ['kode' => 'payment', 'nama' => 'Pembayaran', 'icon' => 'bi-wallet2', 'warna' => 'blue', 'urutan' => 3],
            ['kode' => 'additional', 'nama' => 'Tambahan', 'icon' => 'bi-plus-circle', 'warna' => 'yellow', 'urutan' => 4],
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

        $this->command?->info('Production seed done. Login: admin / admin123');
    }
}
