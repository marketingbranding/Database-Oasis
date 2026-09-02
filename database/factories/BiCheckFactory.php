<?php

namespace Database\Factories;

use App\BiCheckResult;
use App\Models\BiCheck;
use App\Models\SalesCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BiCheck>
 */
class BiCheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sales_case_id' => SalesCase::factory(),
            'check_date' => now()->toDateString(),
            'result' => BiCheckResult::Clear,
            'created_by' => User::factory(),
        ];
    }

    public function result(BiCheckResult $result): static
    {
        return $this->state(fn () => ['result' => $result]);
    }
}
