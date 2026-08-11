<?php

namespace Database\Factories;

use App\Models\Perusahaan;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'id_perusahaan' => Perusahaan::factory(),
            'nama_project' => fake()->sentence(3),
            'client' => fake()->company(),
            'lokasi' => fake()->city(),
            'date_start' => now()->subDays(10)->toDateString(),
            'date_end' => now()->addDays(100)->toDateString(),
            'project_value' => fake()->numberBetween(10000000, 500000000),
            'status' => 'active',
            'mode' => Project::MODE_PROJECT,
            'budget_period' => Project::BUDGET_TOTAL,
        ];
    }

    public function umkm(): static
    {
        return $this->state([
            'mode' => Project::MODE_UMKM,
            'budget_period' => Project::BUDGET_DAILY,
            'daily_budget' => 1000000,
            'business_type' => 'kopi',
        ]);
    }
}
