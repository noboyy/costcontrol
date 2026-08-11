<?php

namespace Database\Factories;

use App\Models\IncomeType;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncomeTypeFactory extends Factory
{
    protected $model = IncomeType::class;

    public function definition(): array
    {
        return [
            'id_perusahaan' => \Database\Factories\PerusahaanFactory::new(),
            'kode' => fake()->unique()->word,
            'nama' => fake()->sentence(2),
            'kategori' => 'sales',
            'default_unit' => 'unit',
        ];
    }
}
