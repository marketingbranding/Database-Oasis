<?php

namespace Database\Factories;

use App\Models\AkadRecord;
use App\Models\DeveloperPpjb;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AkadRecord> */
class AkadRecordFactory extends Factory
{
    public function definition(): array
    {
        $ppjb = DeveloperPpjb::factory()->create();

        return [
            'sales_case_id' => $ppjb->sales_case_id, 'developer_ppjb_id' => $ppjb->id,
            'document_number' => 'AKAD-'.fake()->numerify('####'), 'akad_date' => now()->toDateString(),
            'created_by' => User::factory(),
        ];
    }
}
