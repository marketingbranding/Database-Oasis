<?php

namespace Database\Factories;

use App\DeveloperPpjbStatus;
use App\Models\DeveloperPpjb;
use App\Models\SalesCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeveloperPpjb> */
class DeveloperPpjbFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sales_case_id' => SalesCase::factory(), 'document_date' => now()->toDateString(),
            'document_number' => 'PPJB-'.fake()->numerify('####'), 'status' => DeveloperPpjbStatus::Active,
            'created_by' => User::factory(),
        ];
    }
}
