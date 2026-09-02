<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Unit;
use App\UnitStatus;
use App\UtilityStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'unit_code' => 'K'.fake()->unique()->numerify('###'),
            'block' => fake()->randomLetter(),
            'number' => (string) fake()->numberBetween(1, 200),
            'status' => UnitStatus::Tersedia,
            'building_progress' => fake()->numberBetween(0, 100),
            'electricity_status' => fake()->randomElement(UtilityStatus::cases()),
            'water_status' => fake()->randomElement(UtilityStatus::cases()),
        ];
    }
}
