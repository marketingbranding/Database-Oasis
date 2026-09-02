<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(5)),
            'name' => fake()->company(),
            'city' => fake()->city(),
            'province' => fake()->randomElement([
                'Jawa Tengah',
                'Jawa Barat',
                'Jawa Timur',
                'DKI Jakarta',
                'DI Yogyakarta',
            ]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
