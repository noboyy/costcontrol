<?php

namespace Database\Factories;

use App\Models\CostType;
use Illuminate\Database\Eloquent\Factories\Factory;

class CostTypeFactory extends Factory
{
    protected $model = CostType::class;

    public function definition(): array
    {
        return [
            'id_perusahaan' => \Database\Factories\PerusahaanFactory::new(),
            'kode' => fake()->unique()->word,
            'nama' => fake()->sentence(2),
            'kategori' => 'po',
            'default_unit' => 'unit',
        ];
    }
}
