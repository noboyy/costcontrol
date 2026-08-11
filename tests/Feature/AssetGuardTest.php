<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Akun;
class AssetGuardTest extends TestCase
{
    use RefreshDatabase;
    public function test_super_admin_blocked_from_assets(): void
    {
        $admin = Akun::factory()->superAdmin()->state(['email_verified_at' => now()])->create();
        auth()->login($admin);
        $this->assertEquals(403, $this->get('/asset')->status());
        $this->assertEquals(403, $this->getJson('/api/v1/assets')->status());
    }

    public function test_admin_can_access_assets(): void
    {
        $company = \Database\Factories\PerusahaanFactory::new()->create();
        $pengguna = \Database\Factories\PenggunaFactory::new(['id_perusahaan' => $company->id_perusahaan])->create();
        $admin = Akun::factory()->admin()->state(['id_pengguna' => $pengguna->id_pengguna, 'email_verified_at' => now()])->create();
        auth()->login($admin);
        $this->assertEquals(200, $this->get('/asset')->status());
        $this->assertEquals(200, $this->getJson('/api/v1/assets')->status());
    }
}
