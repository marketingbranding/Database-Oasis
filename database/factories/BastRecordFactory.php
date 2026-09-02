<?php

namespace Database\Factories;

use App\BastStatus;
use App\Models\AkadRecord;
use App\Models\BastRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BastRecord> */
class BastRecordFactory extends Factory
{
    public function definition(): array
    {
        $akad = AkadRecord::factory()->create();

        return [
            'sales_case_id' => $akad->sales_case_id, 'akad_id' => $akad->id,
            'bast_number' => 'BAST-'.fake()->numerify('####'), 'bast_date' => now()->toDateString(),
            'status' => BastStatus::Completed, 'created_by' => User::factory(),
        ];
    }
}
