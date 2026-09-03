<?php

namespace Database\Factories;

use App\Models\AkadTarget;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AkadTarget> */
class AkadTargetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'project_id' => null,
            'period_month' => now()->startOfMonth(),
            'target' => fake()->numberBetween(0, 50),
        ];
    }
}
