<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\CostEntry;
use App\Models\IncomeEntry;
use App\Models\KursHistory;
use App\Models\Pengguna;
use App\Models\Perusahaan;
use App\Models\Project;
use App\Services\KursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PajakKursTest extends TestCase
{
    use RefreshDatabase;

    protected Perusahaan $company;

    protected Akun $admin;

    public function setUp(): void
    {
        parent::setUp();

        $this->company = \Database\Factories\PerusahaanFactory::new()->create();
        $pengguna = \Database\Factories\PenggunaFactory::new(['id_perusahaan' => $this->company->id_perusahaan])->create();
        $this->admin = Akun::factory()->state([
            'id_pengguna' => $pengguna->id_pengguna,
            'role' => 'ADMIN',
        ])->create();
        auth()->login($this->admin);
    }

    private function project(): Project
    {
        return \Database\Factories\ProjectFactory::new(['id_perusahaan' => $this->company->id_perusahaan])->create();
    }

    private function seedIncome(Project $p, float $total): void
    {
        $type = \Database\Factories\IncomeTypeFactory::new(['id_perusahaan' => $this->company->id_perusahaan])->create();
        IncomeEntry::create([
            'id_perusahaan' => $this->company->id_perusahaan,
            'id_project' => $p->id_project,
            'id_income_type' => $type->id_income_type,
            'tanggal' => now()->toDateString(),
            'qty' => 1,
            'harga_satuan' => $total,
            'total' => $total,
        ]);
    }

    public function test_pajak_final_omzet(): void
    {
        $p = $this->project();
        $this->seedIncome($p, 100000000); // omzet 100jt

        $res = $this->get(route('pajak.index'));
        $res->assertStatus(200);
        $res->assertSee('Rp 100.000.000');
        $res->assertSee('Rp 500.000'); // 0.5%
    }

    public function test_pajak_badan_laba_mode(): void
    {
        $p = $this->project();
        $this->seedIncome($p, 100000000);

        $this->company->update(['mode_pajak' => 'badan_laba', 'tarif_pph_laba' => 22.00]);

        $res = $this->get(route('pajak.index'));
        $res->assertStatus(200);
        $res->assertSee('Rp 22.000.000');
    }

    public function test_kurs_history_manual_and_nearest(): void
    {
        KursHistory::create(['tanggal' => '2026-09-01', 'mata_uang' => 'SAR', 'sumber' => 'bi', 'kurs' => 4250.50]);
        KursHistory::create(['tanggal' => '2026-09-05', 'mata_uang' => 'SAR', 'sumber' => 'bi', 'kurs' => 4280.00]);

        $svc = app(KursService::class);
        $this->assertEquals(4250.5, $svc->nearest('2026-09-03', 'SAR', 'bi'));
        $this->assertEquals(4280.0, $svc->nearest('2026-09-10', 'SAR', 'bi'));
    }

    public function test_cost_entry_valas_computed_via_controller(): void
    {
        $p = $this->project();
        $type = \Database\Factories\CostTypeFactory::new(['id_perusahaan' => $this->company->id_perusahaan])->create();

        $res = $this->post(route('cost-centers.addCost', $p->id_project), [
            'id_cost_type' => $type->id_cost_type,
            'tanggal' => now()->toDateString(),
            'keterangan' => 'Hotel 100 SAR',
            'qty' => '1',
            'harga_satuan' => '',
            'mata_uang' => 'SAR',
            'amount_valas' => '100',
            'kurs' => '4250',
            'kurs_sumber' => 'bi',
        ]);

        $res->assertRedirect();
        $entry = CostEntry::where('id_project', $p->id_project)->first();
        $this->assertEquals(100.0, (float) $entry->amount_valas);
        $this->assertEquals(4250.0, (float) $entry->kurs);
        $this->assertEquals('SAR', $entry->mata_uang);
        $this->assertEquals(425000.0, (float) $entry->total);
    }
}
