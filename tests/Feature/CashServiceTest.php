<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\CostEntry;
use App\Models\IncomeEntry;
use App\Models\Perusahaan;
use App\Models\Project;
use App\Services\CashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Perusahaan $company;

    protected Project $project;

    protected CashService $cash;

    public function setUp(): void
    {
        parent::setUp();

        $this->company = \Database\Factories\PerusahaanFactory::new()->create();
        $pengguna = \Database\Factories\PenggunaFactory::new(['id_perusahaan' => $this->company->id_perusahaan])->create();
        $admin = Akun::factory()->state([
            'id_pengguna' => $pengguna->id_pengguna,
            'role' => 'ADMIN',
        ])->create();

        $this->project = \Database\Factories\ProjectFactory::new([
            'id_perusahaan' => $this->company->id_perusahaan,
            'opening_balance' => 5000000,
            'project_value' => '100000000',
        ])->create();

        $this->cash = app(CashService::class);
        auth()->login($admin);
    }

    public function test_initial_position_with_opening_balance(): void
    {
        $pos = $this->cash->position($this->project);

        $this->assertEquals(0.0, $pos['income_to_date']);
        $this->assertEquals(0.0, $pos['cost_to_date']);
        $this->assertEquals(5000000.0, $pos['balance']);
        $this->assertFalse($pos['is_negative']);
    }

    public function test_position_with_income_and_cost(): void
    {
        $today = now()->toDateString();

        IncomeEntry::create([
            'id_perusahaan' => $this->company->id_perusahaan,
            'id_project' => $this->project->id_project,
            'id_income_type' => \Database\Factories\IncomeTypeFactory::new(['id_perusahaan' => $this->company->id_perusahaan])->create()->id_income_type,
            'tanggal' => $today,
            'qty' => 100,
            'harga_satuan' => 50000,
            'total' => 5000000,
        ]);

        CostEntry::create([
            'id_perusahaan' => $this->company->id_perusahaan,
            'id_project' => $this->project->id_project,
            'id_cost_type' => \Database\Factories\CostTypeFactory::new(['id_perusahaan' => $this->company->id_perusahaan])->create()->id_cost_type,
            'tanggal' => $today,
            'qty' => 100,
            'harga_satuan' => 30000,
            'total' => 3000000,
        ]);

        $pos = $this->cash->position($this->project);

        $this->assertEquals(5000000.0, $pos['income_to_date']);
        $this->assertEquals(3000000.0, $pos['cost_to_date']);
        $this->assertEquals(7000000.0, $pos['balance']);
    }

    public function test_series_generates_daily_rows(): void
    {
        $series = $this->cash->series($this->project, now()->subDays(5), now());

        $this->assertCount(6, $series);
        foreach ($series as $row) {
            $this->assertArrayHasKey('date', $row);
            $this->assertArrayHasKey('in', $row);
            $this->assertArrayHasKey('out', $row);
            $this->assertArrayHasKey('balance', $row);
        }
    }

    public function test_forecast_calculates_run_rate(): void
    {
        $costType = \Database\Factories\CostTypeFactory::new(['id_perusahaan' => $this->company->id_perusahaan])->create();

        for ($i = 0; $i < 7; $i++) {
            CostEntry::create([
                'id_perusahaan' => $this->company->id_perusahaan,
                'id_project' => $this->project->id_project,
                'id_cost_type' => $costType->id_cost_type,
                'tanggal' => now()->subDays(6 - $i)->toDateString(),
                'qty' => 100,
                'harga_satuan' => 20000,
                'total' => 2000000,
            ]);
        }

        $forecast = $this->cash->forecast($this->project);

        $this->assertEquals(7, $forecast['window_days']);
        $this->assertEquals(2000000.0, $forecast['avg_daily_cost']);
        $this->assertEquals(0.0, $forecast['avg_daily_income']);
        $this->assertNotNull($forecast['days_to_deplete']);
    }

    public function test_api_cash_endpoint(): void
    {
        $response = $this->getJson('/api/v1/projects/'.$this->project->id_project.'/cash');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'position' => ['date', 'opening', 'income_to_date', 'cost_to_date', 'balance', 'is_negative'],
                    'forecast' => ['window_days', 'avg_daily_cost', 'avg_daily_income', 'net_burn_daily', 'current_balance', 'budget_target', 'days_to_deplete', 'projected_end_cost'],
                ],
            ]);
    }
}
