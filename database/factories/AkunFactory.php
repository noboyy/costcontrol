<?php

namespace Database\Factories;

use App\Models\Akun;
use App\Models\Pengguna;
use App\Models\Perusahaan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Akun>
 */
class AkunFactory extends Factory
{
    protected $model = Akun::class;

    public function definition(): array
    {
        return [
            'id_pengguna' => null,
            'username' => fake()->userName(),
            'email' => null,
            'role' => 'USER',
            'password' => Hash::make('password'),
            'is_active' => '1',
            'change_password' => 0,
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(['role' => 'SUPER ADMIN']);
    }

    public function admin(): static
    {
        return $this->state(['role' => 'ADMIN']);
    }
}
