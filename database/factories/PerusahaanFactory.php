<?php

namespace Database\Factories;

use App\Models\Perusahaan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Perusahaan>
 */
class PerusahaanFactory extends Factory
{
    protected $model = Perusahaan::class;

    public function definition(): array
    {
        return [
            'nama_perusahaan' => fake()->company(),
            'alamat_lengkap' => fake()->address(),
            'owner' => fake()->name(),
            'module' => Perusahaan::MODULE_ALL,
        ];
    }
}
