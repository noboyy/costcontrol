<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\CostEntry;
use App\Models\Pengguna;
use App\Models\Project;
use App\Services\CashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralExpenseTest extends TestCase
{
    use RefreshDatabase;

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

    private function costType()
    {
        return \Database\Factories\CostTypeFactory::new(['id_perusahaan' => $this->company->id_perusahaan])->create();
    }

    public function test_admin_can_create_general_expense_without_project(): void
    {
        $response = $this->post('/biaya-umum', [
            'id_cost_type' => $this->costType()->id_cost_type,
            'tanggal' => now()->toDateString(),
            'keterangan' => 'Sewa kantor',
            'total' => '2500000',
        ]);

        $response->assertRedirect(route('general-expenses.index'));
        $this->assertDatabaseHas('cost_entry', [
            'id_project' => null,
            'keterangan' => 'Sewa kantor',
            'total' => 2500000,
        ]);
    }

    public function test_admin_can_view_general_expense_page(): void
    {
        $response = $this->get(route('general-expenses.index'));

        $response->assertStatus(200);
        $response->assertSee('Pengeluaran Umum');
    }

    public function test_dashboard_shows_company_cash(): void
    {
        $response = $this->get(route('beranda'));

        $response->assertStatus(200);
        $response->assertSee('Kas Perusahaan');
    }

    public function test_company_cash_position_includes_general_expense(): void
    {
        $project = \Database\Factories\ProjectFactory::new([
            'id_perusahaan' => $this->company->id_perusahaan,
            'opening_balance' => 10000000,
        ])->create();

        $type = $this->costType();
        CostEntry::create([
            'id_perusahaan' => $this->company->id_perusahaan,
            'id_project' => null,
            'id_cost_type' => $type->id_cost_type,
            'tanggal' => now()->toDateString(),
            'qty' => 1,
            'harga_satuan' => 1500000,
            'total' => 1500000,
        ]);

        $pos = app(CashService::class)->positionCompany($this->company->id_perusahaan);

        $this->assertEquals(10000000, $pos['opening']);
        $this->assertEquals(1500000, $pos['cost_general_to_date']);
        $this->assertEquals(8500000, $pos['balance']);
    }

    public function test_general_expense_not_counted_as_project_cost(): void
    {
        $project = \Database\Factories\ProjectFactory::new([
            'id_perusahaan' => $this->company->id_perusahaan,
        ])->create();

        $type = $this->costType();
        CostEntry::create([
            'id_perusahaan' => $this->company->id_perusahaan,
            'id_project' => null,
            'id_cost_type' => $type->id_cost_type,
            'tanggal' => now()->toDateString(),
            'qty' => 1,
            'harga_satuan' => 500000,
            'total' => 500000,
        ]);

        $this->assertEquals(0.0, $project->total_cost);
        $this->assertCount(0, Project::find($project->id_project)->costEntries);
    }
}
