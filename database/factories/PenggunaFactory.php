<?php

namespace Database\Factories;

use App\Models\Pengguna;
use App\Models\Perusahaan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengguna>
 */
class PenggunaFactory extends Factory
{
    protected $model = Pengguna::class;

    public function definition(): array
    {
        return [
            'id_perusahaan' => Perusahaan::factory(),
            'nama_lengkap' => fake()->name(),
            'no_hp' => fake()->phoneNumber(),
            'alamat' => fake()->address(),
            'jabatan' => 'Admin',
        ];
    }
}
