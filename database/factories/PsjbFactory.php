<?php

namespace Database\Factories;

use App\Models\Psjb;
use App\Models\SalesCase;
use App\Models\User;
use App\PsjbStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Psjb>
 */
class PsjbFactory extends Factory
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
            'psjb_date' => now()->toDateString(),
            'document_number' => 'PSJB-'.fake()->unique()->numerify('####'),
            'status' => PsjbStatus::Active,
            'created_by' => User::factory(),
        ];
    }

    public function status(PsjbStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
