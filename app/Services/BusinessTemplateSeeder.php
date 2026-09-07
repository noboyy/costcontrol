<?php

namespace App\Services;

use App\Models\CostCategory;
use App\Models\CostGroup;
use App\Models\CostType;
use App\Models\IncomeCategory;
use App\Models\IncomeType;

class BusinessTemplateSeeder
{
    /**
     * Template master data Travel Umroh untuk Sahla Journey Finance.
     * Safe to call multiple times (idempotent by kode).
     */
    public function seedTravelUmroh(?int $companyId): void
    {
        if (! $companyId) {
            return;
        }

        $this->seedCostGroups($companyId);
        $this->seedCostCategories($companyId);
        $this->seedCostTypes($companyId);
        $this->seedIncomeCategories($companyId);
        $this->seedIncomeTypes($companyId);
    }

    private function seedCostGroups(int $companyId): void
    {
        $groups = [
            ['kode' => 'direct', 'nama' => 'Direct Trip — Biaya per Jemaah/Trip', 'warna' => 'blue', 'urutan' => 1],
            ['kode' => 'overhead', 'nama' => 'Overhead Kantor', 'warna' => 'yellow', 'urutan' => 2],
            ['kode' => 'oc', 'nama' => 'Biaya Lain', 'warna' => 'gray', 'urutan' => 3],
        ];

        foreach ($groups as $g) {
            CostGroup::updateOrCreate(
                ['id_perusahaan' => $companyId, 'kode' => $g['kode']],
                array_merge($g, [
                    'id_perusahaan' => $companyId,
                    'is_active' => true,
                ])
            );
        }
    }

    private function seedCostCategories(int $companyId): void
    {
        $categories = [
            ['kode' => 'tiket_transport', 'nama' => 'Tiket & Transportasi Udara', 'icon' => 'bi-airplane', 'warna' => 'blue', 'urutan' => 1, 'kelompok' => 'direct'],
            ['kode' => 'visa_dokumen', 'nama' => 'Visa & Dokumen Jemaah', 'icon' => 'bi-passport', 'warna' => 'green', 'urutan' => 2, 'kelompok' => 'direct'],
            ['kode' => 'akomodasi', 'nama' => 'Hotel & Akomodasi', 'icon' => 'bi-building', 'warna' => 'blue', 'urutan' => 3, 'kelompok' => 'direct'],
            ['kode' => 'konsumsi', 'nama' => 'Makan & Konsumsi', 'icon' => 'bi-egg-fried', 'warna' => 'yellow', 'urutan' => 4, 'kelompok' => 'direct'],
            ['kode' => 'transport_darat', 'nama' => 'Transportasi Darat', 'icon' => 'bi-bus-front', 'warna' => 'blue', 'urutan' => 5, 'kelompok' => 'direct'],
            ['kode' => 'handling_ziarah', 'nama' => 'Handling & Ziarah', 'icon' => 'bi-person-badge', 'warna' => 'green', 'urutan' => 6, 'kelompok' => 'direct'],
            ['kode' => 'atribut_jemaah', 'nama' => 'Atribut & Perlengkapan Jemaah', 'icon' => 'bi-bag', 'warna' => 'yellow', 'urutan' => 7, 'kelompok' => 'direct'],
            ['kode' => 'ops_kantor', 'nama' => 'Operasional Kantor', 'icon' => 'bi-briefcase', 'warna' => 'gray', 'urutan' => 8, 'kelompok' => 'overhead'],
            ['kode' => 'sdm', 'nama' => 'SDM & Komisi', 'icon' => 'bi-people', 'warna' => 'green', 'urutan' => 9, 'kelompok' => 'overhead'],
            ['kode' => 'marketing', 'nama' => 'Marketing & Agen', 'icon' => 'bi-megaphone', 'warna' => 'blue', 'urutan' => 10, 'kelompok' => 'overhead'],
            ['kode' => 'pajak_admin', 'nama' => 'Pajak & Administrasi', 'icon' => 'bi-receipt', 'warna' => 'red', 'urutan' => 11, 'kelompok' => 'oc'],
            ['kode' => 'other', 'nama' => 'Lainnya', 'icon' => 'bi-three-dots', 'warna' => 'gray', 'urutan' => 99, 'kelompok' => 'oc'],
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
    }

    private function seedCostTypes(int $companyId): void
    {
        $costTypes = [
            // Tiket & transportasi udara
            ['kode' => 'TKT', 'nama' => 'Tiket Penerbangan CGK-JED', 'kategori' => 'tiket_transport', 'default_unit' => 'Orang'],
            ['kode' => 'TKR', 'nama' => 'Tiket Penerbangan JED-CGK (Return)', 'kategori' => 'tiket_transport', 'default_unit' => 'Orang'],
            ['kode' => 'TKT2', 'nama' => 'Tiket Transit / Connecting Flight', 'kategori' => 'tiket_transport', 'default_unit' => 'Orang'],
            ['kode' => 'BGS', 'nama' => 'Bagasi Ekstra', 'kategori' => 'tiket_transport', 'default_unit' => 'Kilogram'],
            ['kode' => 'RUB', 'nama' => 'Rebooking / Rute Change', 'kategori' => 'tiket_transport', 'default_unit' => null],

            // Visa & dokumen
            ['kode' => 'VSA', 'nama' => 'Visa Umroh', 'kategori' => 'visa_dokumen', 'default_unit' => 'Orang'],
            ['kode' => 'PSP', 'nama' => 'Paspor (buat/perpanjang)', 'kategori' => 'visa_dokumen', 'default_unit' => 'Orang'],
            ['kode' => 'VKS', 'nama' => 'Vaksin Meningitis', 'kategori' => 'visa_dokumen', 'default_unit' => 'Orang'],
            ['kode' => 'ASR', 'nama' => 'Asuransi Perjalanan', 'kategori' => 'visa_dokumen', 'default_unit' => 'Orang'],
            ['kode' => 'LGL', 'nama' => 'Legalisir & Fotokopi Dokumen', 'kategori' => 'visa_dokumen', 'default_unit' => 'Lembar'],

            // Hotel & akomodasi
            ['kode' => 'HMK', 'nama' => 'Hotel Makkah', 'kategori' => 'akomodasi', 'default_unit' => 'Malam'],
            ['kode' => 'HMD', 'nama' => 'Hotel Madinah', 'kategori' => 'akomodasi', 'default_unit' => 'Malam'],
            ['kode' => 'HTS', 'nama' => 'Hotel Transit', 'kategori' => 'akomodasi', 'default_unit' => 'Malam'],
            ['kode' => 'UPG', 'nama' => 'Upgrade Kamar Hotel', 'kategori' => 'akomodasi', 'default_unit' => 'Malam'],
            ['kode' => 'XBD', 'nama' => 'Extra Bed Hotel', 'kategori' => 'akomodasi', 'default_unit' => 'Malam'],

            // Makan & konsumsi
            ['kode' => 'PKM', 'nama' => 'Paket Makan Makkah', 'kategori' => 'konsumsi', 'default_unit' => 'Hari'],
            ['kode' => 'PKD', 'nama' => 'Paket Makan Madinah', 'kategori' => 'konsumsi', 'default_unit' => 'Hari'],
            ['kode' => 'PKT', 'nama' => 'Paket Makan Transit', 'kategori' => 'konsumsi', 'default_unit' => 'Hari'],
            ['kode' => 'KSM', 'nama' => 'Konsumsi Manasik / Briefing', 'kategori' => 'konsumsi', 'default_unit' => 'Orang'],
            ['kode' => 'SNK', 'nama' => 'Snack & Air Mineral Trip', 'kategori' => 'konsumsi', 'default_unit' => 'Paket'],

            // Transportasi darat
            ['kode' => 'BSA', 'nama' => 'Bus Bandara (Jeddah)', 'kategori' => 'transport_darat', 'default_unit' => 'Orang'],
            ['kode' => 'BSZ', 'nama' => 'Bus Ziarah / City Tour', 'kategori' => 'transport_darat', 'default_unit' => 'Unit'],
            ['kode' => 'KOP', 'nama' => 'Kendaraan Operasional Trip', 'kategori' => 'transport_darat', 'default_unit' => 'Hari'],
            ['kode' => 'BBM', 'nama' => 'Bensin & Parkir Trip', 'kategori' => 'transport_darat', 'default_unit' => 'Hari'],
            ['kode' => 'TRJ', 'nama' => 'Transport Jemaah Domestic (ke bandara asal)', 'kategori' => 'transport_darat', 'default_unit' => 'Orang'],

            // Handling & ziarah
            ['kode' => 'MSM', 'nama' => 'Fee Muassim / Handling Saudi', 'kategori' => 'handling_ziarah', 'default_unit' => 'Orang'],
            ['kode' => 'MTW', 'nama' => 'Fee Muthawif / Muballigh', 'kategori' => 'handling_ziarah', 'default_unit' => 'Orang'],
            ['kode' => 'TLF', 'nama' => 'Fee Tour Leader & Tim', 'kategori' => 'handling_ziarah', 'default_unit' => 'Trip'],
            ['kode' => 'TZR', 'nama' => 'Tiket Ziarah (Raudhah dll)', 'kategori' => 'handling_ziarah', 'default_unit' => 'Orang'],
            ['kode' => 'HBL', 'nama' => 'Handling Bandara & Imigrasi', 'kategori' => 'handling_ziarah', 'default_unit' => 'Trip'],

            // Atribut & perlengkapan jemaah
            ['kode' => 'KPR', 'nama' => 'Koper Jemaah', 'kategori' => 'atribut_jemaah', 'default_unit' => 'Orang'],
            ['kode' => 'IDC', 'nama' => 'ID Card & Sling Bag', 'kategori' => 'atribut_jemaah', 'default_unit' => 'Orang'],
            ['kode' => 'MKN', 'nama' => 'Mukena / Jubah Jemaah', 'kategori' => 'atribut_jemaah', 'default_unit' => 'Orang'],
            ['kode' => 'BMS', 'nama' => 'Buku Manasik & Panduan', 'kategori' => 'atribut_jemaah', 'default_unit' => 'Orang'],
            ['kode' => 'BDR', 'nama' => 'Bendera Group & Identitas', 'kategori' => 'atribut_jemaah', 'default_unit' => 'Set'],

            // Operasional kantor
            ['kode' => 'SWK', 'nama' => 'Sewa Kantor', 'kategori' => 'ops_kantor', 'default_unit' => 'Bulan'],
            ['kode' => 'LST', 'nama' => 'Listrik & Air Kantor', 'kategori' => 'ops_kantor', 'default_unit' => 'Bulan'],
            ['kode' => 'NET', 'nama' => 'Internet & Telepon', 'kategori' => 'ops_kantor', 'default_unit' => 'Bulan'],
            ['kode' => 'ATK', 'nama' => 'ATK & Percetakan', 'kategori' => 'ops_kantor', 'default_unit' => null],
            ['kode' => 'PLS', 'nama' => 'Pulsa & Kuota Staff', 'kategori' => 'ops_kantor', 'default_unit' => 'Bulan'],
            ['kode' => 'RPT', 'nama' => 'Reparasi & Pemeliharaan Kantor', 'kategori' => 'ops_kantor', 'default_unit' => null],

            // SDM & komisi
            ['kode' => 'GJI', 'nama' => 'Gaji Staff Kantor', 'kategori' => 'sdm', 'default_unit' => 'Bulan'],
            ['kode' => 'KMS', 'nama' => 'Komisi Agen / Gressor', 'kategori' => 'sdm', 'default_unit' => 'Orang'],
            ['kode' => 'INS', 'nama' => 'Insentif Tour Leader', 'kategori' => 'sdm', 'default_unit' => 'Trip'],
            ['kode' => 'HNU', 'nama' => 'Honor Ustadz / Pembina Manasik', 'kategori' => 'sdm', 'default_unit' => 'Orang'],
            ['kode' => 'THR', 'nama' => 'Bonus & THR Staff', 'kategori' => 'sdm', 'default_unit' => 'Bulan'],

            // Marketing & agen
            ['kode' => 'IKL', 'nama' => 'Iklan & Promosi Digital', 'kategori' => 'marketing', 'default_unit' => 'Bulan'],
            ['kode' => 'SMP', 'nama' => 'Seminar / Expo Umroh', 'kategori' => 'marketing', 'default_unit' => 'Trip'],
            ['kode' => 'WBS', 'nama' => 'Website & Media Sosial', 'kategori' => 'marketing', 'default_unit' => 'Bulan'],
            ['kode' => 'SOU', 'nama' => 'Souvenir & Merchandise Calon Jemaah', 'kategori' => 'marketing', 'default_unit' => 'Orang'],

            // Pajak & administrasi
            ['kode' => 'PJK', 'nama' => 'Pajak & Retribusi', 'kategori' => 'pajak_admin', 'default_unit' => 'Bulan'],
            ['kode' => 'BNK', 'nama' => 'Biaya Bank & Admin Transfer', 'kategori' => 'pajak_admin', 'default_unit' => null],
            ['kode' => 'ROY', 'nama' => 'Royalti / Lisensi Biro', 'kategori' => 'pajak_admin', 'default_unit' => 'Trip'],
            ['kode' => 'ZKT', 'nama' => 'Zakat / Infaq / Sedekah', 'kategori' => 'pajak_admin', 'default_unit' => null],
            ['kode' => 'LNN', 'nama' => 'Biaya Lain-lain', 'kategori' => 'other', 'default_unit' => null],
        ];

        foreach ($costTypes as $type) {
            CostType::updateOrCreate(
                ['id_perusahaan' => $companyId, 'kode' => $type['kode']],
                array_merge($type, [
                    'id_perusahaan' => $companyId,
                    'default_unit' => $type['default_unit'],
                ])
            );
        }
    }

    private function seedIncomeCategories(int $companyId): void
    {
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
                ['id_perusahaan' => $companyId, 'kode' => $c['kode']],
                array_merge($c, ['id_perusahaan' => $companyId, 'is_active' => true])
            );
        }

        // Sync orphan income type categories
        $used = IncomeType::where('id_perusahaan', $companyId)
            ->pluck('kategori')->filter()->map(fn ($k) => strtolower(trim($k)))->unique();
        $order = 10;
        foreach ($used as $kode) {
            IncomeCategory::firstOrCreate(
                ['id_perusahaan' => $companyId, 'kode' => $kode],
                [
                    'nama' => ucfirst(str_replace('_', ' ', $kode)),
                    'icon' => 'bi-folder',
                    'warna' => 'green',
                    'urutan' => $order++,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedIncomeTypes(int $companyId): void
    {
        $incomeTypes = [
            // Pendaftaran jemaah
            ['kode' => 'REG', 'nama' => 'Biaya Pendaftaran Jemaah', 'kategori' => 'pendaftaran', 'default_unit' => null],
            ['kode' => 'DPJ', 'nama' => 'DP Jemaah', 'kategori' => 'pembayaran', 'default_unit' => null],
            ['kode' => 'CIC', 'nama' => 'Cicilan Jemaah', 'kategori' => 'pembayaran', 'default_unit' => null],
            ['kode' => 'PLL', 'nama' => 'Pelunasan Paket', 'kategori' => 'pembayaran', 'default_unit' => null],
            ['kode' => 'TRF', 'nama' => 'Pembayaran Transfer / QRIS', 'kategori' => 'pembayaran', 'default_unit' => null],
            ['kode' => 'TUN', 'nama' => 'Pembayaran Tunai', 'kategori' => 'pembayaran', 'default_unit' => null],

            // Paket
            ['kode' => 'PKR', 'nama' => 'Paket Reguler', 'kategori' => 'paket', 'default_unit' => 'Orang'],
            ['kode' => 'PKP', 'nama' => 'Paket Plus (Turki/Dubai)', 'kategori' => 'paket', 'default_unit' => 'Orang'],
            ['kode' => 'PKH', 'nama' => 'Paket Haji Khusus', 'kategori' => 'paket', 'default_unit' => 'Orang'],

            // Tambahan
            ['kode' => 'UPK', 'nama' => 'Upgrade Kamar', 'kategori' => 'tambahan', 'default_unit' => null],
            ['kode' => 'SGL', 'nama' => 'Single Supplement', 'kategori' => 'tambahan', 'default_unit' => null],
            ['kode' => 'EKT', 'nama' => 'Ekstra Berangkat / Perpanjangan', 'kategori' => 'tambahan', 'default_unit' => null],

            // Komisi & jasa
            ['kode' => 'KAG', 'nama' => 'Komisi dari Biro Partner', 'kategori' => 'komisi', 'default_unit' => null],
            ['kode' => 'VHF', 'nama' => 'Visa Handling Fee (jasa urus)', 'kategori' => 'komisi', 'default_unit' => null],
            ['kode' => 'JMS', 'nama' => 'Jasa Manasik / Briefing', 'kategori' => 'komisi', 'default_unit' => null],
            ['kode' => 'PLN2', 'nama' => 'Pendapatan Lain-lain', 'kategori' => 'other', 'default_unit' => null],
        ];

        foreach ($incomeTypes as $type) {
            IncomeType::updateOrCreate(
                ['id_perusahaan' => $companyId, 'kode' => $type['kode']],
                array_merge($type, [
                    'id_perusahaan' => $companyId,
                    'default_unit' => $type['default_unit'],
                ])
            );
        }
    }
}
