<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Akun;
class SuperAdminRedirectTest extends TestCase
{
    use RefreshDatabase;
    public function test_super_admin_redirected_to_stats(): void
    {
        $admin = Akun::factory()->superAdmin()->state(['email_verified_at' => now()])->create();
        auth()->login($admin);
        $r = $this->get('/');
        $this->assertEquals(302, $r->status());
        $this->assertStringEndsWith('/super-admin/stats', $r->headers->get('Location'));
    }
}
