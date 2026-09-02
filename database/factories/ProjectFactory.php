<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Project;
use App\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'code' => strtoupper(Str::random(5)),
            'name' => fake()->company(),
            'location' => fake()->address(),
            'status' => ProjectStatus::Aktif,
        ];
    }
}
