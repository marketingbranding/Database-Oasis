<?php

namespace Database\Factories;

use App\Models\CaseNote;
use App\Models\SalesCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CaseNote> */
class CaseNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sales_case_id' => SalesCase::factory(),
            'note' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
